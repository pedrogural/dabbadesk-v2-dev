<?php

namespace App\Services\Purchasing;

use Illuminate\Support\Collection;
use App\Services\Retailers\RetailerRegistrationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class PurchaseDeskV2Service
{
    private ?ItemLifecycleService $itemLifecycleService = null;

    private function itemLifecycle(): ItemLifecycleService
    {
        return $this->itemLifecycleService ??= new ItemLifecycleService();
    }

    public function index(array $filters = []): array
    {
        $q = trim((string) ($filters['q'] ?? ''));
        $payment = (string) ($filters['payment'] ?? 'paid_or_part');
        $myOnly = (bool) ($filters['my'] ?? false);
        $userId = ! empty($filters['user_id']) ? (int) $filters['user_id'] : null;

        // Pass 2 speed rule:
        // 1) get a small set of candidate active orders first;
        // 2) aggregate purchase/arrival/problem quantities only for those orders;
        // 3) filter the operational queue in PHP.
        // This avoids running expensive lifecycle aggregates across every historical order.
        $settlementTotals = $this->settlementTotalsSubquery();

        $candidateQuery = DB::table('orders as o')
            ->leftJoin('users as u', 'u.id', '=', 'o.created_by_user_id')
            ->leftJoinSub($settlementTotals, 'st', fn ($join) => $join->on('st.order_id', '=', 'o.id'))
            ->select([
                'o.id',
                'o.draft_order_id',
                'o.parent_order_id',
                'o.order_number',
                'o.status',
                'o.purchase_mode',
                'o.bill_to_name',
                'o.bill_to_company',
                'o.bill_to_email',
                'o.bill_to_phone',
                'o.bill_to_address_line1',
                'o.bill_to_postcode',
                'o.grand_total',
                'o.created_at',
                'u.name as operator_name',
                DB::raw('COALESCE(st.settled_amount, 0) as settled_amount'),
                DB::raw('GREATEST(0, COALESCE(o.grand_total, 0) - COALESCE(st.settled_amount, 0)) as balance_due'),
            ])
;

        $this->itemLifecycle()->applyLivePurchasingOrderConstraints($candidateQuery, 'o');

        if ($myOnly && $userId) {
            $candidateQuery->where('o.created_by_user_id', $userId);
        }

        if ($payment === 'paid_or_part') {
            $candidateQuery->where(function ($where) {
                $where->whereIn('o.status', ['paid', 'partially_paid'])
                    ->orWhereRaw('COALESCE(st.settled_amount, 0) > 0');
            });
        } elseif ($payment === 'pending_payment') {
            $candidateQuery->where(function ($where) {
                $where->whereNotIn('o.status', ['paid', 'cancelled', 'refunded', 'superseded'])
                    ->whereRaw('GREATEST(0, COALESCE(o.grand_total, 0) - COALESCE(st.settled_amount, 0)) > 0');
            });
        }

        if ($q !== '') {
            $needle = '%' . mb_strtolower($q) . '%';
            $candidateQuery->where(function ($search) use ($needle) {
                $search->whereRaw('LOWER(o.order_number) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(COALESCE(o.bill_to_name, "")) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(COALESCE(o.bill_to_company, "")) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(COALESCE(o.bill_to_email, "")) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(COALESCE(o.bill_to_phone, "")) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(COALESCE(o.bill_to_address_line1, "")) LIKE ?', [$needle])
                    ->orWhereExists(function ($items) use ($needle) {
                        $items->from('order_items as oi')
                            ->whereColumn('oi.order_id', 'o.id')
                            ->where(function ($itemSearch) use ($needle) {
                                $itemSearch->whereRaw('LOWER(COALESCE(oi.item_name, "")) LIKE ?', [$needle])
                                    ->orWhereRaw('LOWER(COALESCE(oi.description, "")) LIKE ?', [$needle])
                                    ->orWhereRaw('LOWER(COALESCE(oi.product_code, "")) LIKE ?', [$needle])
                                    ->orWhereRaw('LOWER(COALESCE(oi.product_url, "")) LIKE ?', [$needle]);
                            });
                    });
            });
        }

        $candidates = $candidateQuery
            ->orderByDesc('o.created_at')
            ->limit(600)
            ->get();

        $candidateIds = $candidates->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        $lineageOrderIds = $this->ancestorOrderIdsForOrderIds($candidates);

        if (empty($candidateIds)) {
            return [
                'orders' => collect(),
                'filters' => ['q' => $q, 'payment' => $payment, 'my' => $myOnly],
                'summary' => [
                    'orders_count' => 0,
                    'active_item_qty' => 0,
                    'remaining_to_buy_qty' => 0,
                    'purchased_qty' => 0,
                    'arrived_qty' => 0,
                    'pre_purchase_problem_qty' => 0,
                ],
            ];
        }

        $itemTotals = DB::query()
            ->fromSub($this->orderItemTotalsSubquery($candidateIds, $lineageOrderIds), 'it')
            ->get()
            ->keyBy('order_id');

        $orders = $candidates
            ->map(function ($order) use ($itemTotals) {
                $totals = $itemTotals->get($order->id);

                $order->active_item_qty = (int) ($totals->active_item_qty ?? 0);
                $order->purchased_qty = (int) ($totals->purchased_qty ?? 0);
                $order->awaiting_arrival_qty = (int) ($totals->awaiting_arrival_qty ?? 0);
                $order->arrived_qty = (int) ($totals->arrived_qty ?? 0);
                $order->remaining_to_buy_qty = (int) ($totals->remaining_to_buy_qty ?? 0);
                $order->pre_purchase_problem_qty = (int) ($totals->pre_purchase_problem_qty ?? 0);

                return $order;
            })
            ->filter(fn ($order) => $order->remaining_to_buy_qty > 0 || $order->pre_purchase_problem_qty > 0)
            ->sortBy([
                fn ($a, $b) => ($b->remaining_to_buy_qty > 0) <=> ($a->remaining_to_buy_qty > 0),
                fn ($a, $b) => strcmp((string) $b->created_at, (string) $a->created_at),
            ])
            ->take(150)
            ->values();

        return [
            'orders' => $orders,
            'filters' => [
                'q' => $q,
                'payment' => $payment,
                'my' => $myOnly,
            ],
            'summary' => [
                'orders_count' => $orders->count(),
                'active_item_qty' => (int) $orders->sum('active_item_qty'),
                'remaining_to_buy_qty' => (int) $orders->sum('remaining_to_buy_qty'),
                'purchased_qty' => (int) $orders->sum('purchased_qty'),
                'arrived_qty' => (int) $orders->sum('arrived_qty'),
                'pre_purchase_problem_qty' => (int) $orders->sum('pre_purchase_problem_qty'),
            ],
        ];
    }


    public function purchaseHistory(array $filters = []): array
    {
        $q = trim((string) ($filters['q'] ?? ''));
        $status = (string) ($filters['status'] ?? 'all');
        $retailerId = ! empty($filters['retailer_id']) ? (int) $filters['retailer_id'] : null;
        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        $myOnly = (bool) ($filters['my'] ?? false);
        $userId = ! empty($filters['user_id']) ? (int) $filters['user_id'] : null;

        $arrivalTotals = DB::table('purchase_arrival_assignments as paa')
            ->selectRaw('paa.order_item_purchase_id')
            ->selectRaw('SUM(CASE WHEN paa.undone_at IS NULL THEN paa.qty ELSE 0 END) as arrived_qty')
            ->selectRaw("SUM(CASE WHEN paa.undone_at IS NULL AND paa.status IN ('ready_for_collection','for_delivery') THEN paa.qty ELSE 0 END) as ready_qty")
            ->selectRaw("SUM(CASE WHEN paa.undone_at IS NULL AND paa.status = 'collected' THEN paa.qty ELSE 0 END) as collected_qty")
            ->selectRaw("SUM(CASE WHEN paa.undone_at IS NULL AND paa.status = 'delivered' THEN paa.qty ELSE 0 END) as delivered_qty")
            ->selectRaw('MAX(paa.matched_at) as latest_arrival_at')
            ->selectRaw('MAX(paa.status_updated_at) as latest_arrival_status_at')
            ->selectRaw('MAX(paa.status) as latest_arrival_status')
            ->whereNull('paa.undone_at')
            ->groupBy('paa.order_item_purchase_id');

        $informedTotals = DB::table('purchase_arrival_assignments as paa')
            ->join('customer_release_notification_items as crni', 'crni.purchase_arrival_assignment_id', '=', 'paa.id')
            ->join('customer_release_notifications as crn', function ($join) {
                $join->on('crn.id', '=', 'crni.customer_release_notification_id')
                    ->whereNotNull('crn.sent_at');
            })
            ->selectRaw('paa.order_item_purchase_id')
            ->selectRaw('COUNT(DISTINCT crni.id) as informed_count')
            ->selectRaw('MAX(crn.sent_at) as latest_informed_at')
            ->whereNull('paa.undone_at')
            ->groupBy('paa.order_item_purchase_id');

        $query = DB::table('order_item_purchases as oip')
            ->join('orders as o', 'o.id', '=', 'oip.order_id')
            ->leftJoin('order_items as oi', 'oi.id', '=', 'oip.order_item_id')
            ->leftJoin('retailers as r', 'r.id', '=', 'oip.retailer_id')
            ->leftJoin('users as buyer', 'buyer.id', '=', 'oip.created_by_user_id')
            ->leftJoinSub($arrivalTotals, 'at', fn ($join) => $join->on('at.order_item_purchase_id', '=', 'oip.id'))
            ->leftJoinSub($informedTotals, 'it', fn ($join) => $join->on('it.order_item_purchase_id', '=', 'oip.id'))
            ->whereIn('oip.status', ['purchased', 'ordered', 'received'])
            ->whereNull('oip.cancelled_at')
            ->select([
                'oip.id',
                'oip.order_id',
                'oip.order_item_id',
                'oip.root_item_id',
                'oip.qty',
                'oip.purchase_unit_price',
                'oip.purchase_line_total',
                'oip.currency',
                'oip.retailer_id',
                'oip.retailer_order_reference',
                'oip.marketplace_seller',
                'oip.ordered_at',
                'oip.estimated_retailer_delivery_date',
                'oip.note',
                'oip.created_at',
                'oip.created_by_user_id',
                'o.order_number',
                'o.bill_to_name',
                'o.bill_to_company',
                'o.status as order_status',
                'oi.item_name',
                'oi.description',
                'oi.product_code',
                'oi.product_url',
                'oi.quantity as ordered_qty',
                'oi.status as item_status',
                DB::raw('COALESCE(r.name, "Unknown retailer") as retailer_name'),
                'buyer.name as purchased_by_name',
                DB::raw('COALESCE(at.arrived_qty, 0) as arrived_qty'),
                DB::raw('COALESCE(at.ready_qty, 0) as ready_qty'),
                DB::raw('COALESCE(at.collected_qty, 0) as collected_qty'),
                DB::raw('COALESCE(at.delivered_qty, 0) as delivered_qty'),
                DB::raw('COALESCE(it.informed_count, 0) as informed_count'),
                'it.latest_informed_at',
                'at.latest_arrival_at',
                'at.latest_arrival_status_at',
                'at.latest_arrival_status',
            ]);

        $this->itemLifecycle()->applyLivePurchasingOrderConstraints($query, 'o');

        if ($myOnly && $userId) {
            $query->where('oip.created_by_user_id', $userId);
        }

        if ($retailerId) {
            $query->where('oip.retailer_id', $retailerId);
        }

        if ($dateFrom !== '') {
            $query->whereDate(DB::raw('COALESCE(oip.ordered_at, oip.created_at)'), '>=', $dateFrom);
        }

        if ($dateTo !== '') {
            $query->whereDate(DB::raw('COALESCE(oip.ordered_at, oip.created_at)'), '<=', $dateTo);
        }

        if ($q !== '') {
            $needle = '%' . mb_strtolower($q) . '%';
            $query->where(function ($search) use ($needle) {
                $search->whereRaw('LOWER(COALESCE(o.order_number, "")) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(COALESCE(o.bill_to_name, "")) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(COALESCE(o.bill_to_company, "")) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(COALESCE(oi.item_name, "")) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(COALESCE(oi.description, "")) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(COALESCE(oi.product_code, "")) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(COALESCE(oi.product_url, "")) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(COALESCE(r.name, "")) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(COALESCE(oip.retailer_order_reference, "")) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(COALESCE(oip.marketplace_seller, "")) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(COALESCE(oip.note, "")) LIKE ?', [$needle]);
            });
        }

        $rows = $query
            ->orderByDesc(DB::raw('COALESCE(oip.ordered_at, oip.created_at)'))
            ->orderByDesc('oip.id')
            ->limit(400)
            ->get()
            ->map(fn ($row) => $this->decoratePurchasedHistoryRow($row));

        if ($status !== 'all') {
            $rows = $rows->filter(fn ($row) => (string) $row->lifecycle_key === $status)->values();
        }

        return [
            'purchases' => $rows,
            'filters' => [
                'q' => $q,
                'status' => $status,
                'retailer_id' => $retailerId,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'my' => $myOnly,
            ],
            'retailers' => $this->activeSuppliers(),
            'summary' => [
                'lines_count' => $rows->count(),
                'units_count' => (int) $rows->sum('qty'),
                'purchase_total' => (float) $rows->sum(fn ($row) => (float) ($row->purchase_line_total ?? 0)),
                'pending_arrival_count' => $rows->where('lifecycle_key', 'pending_arrival')->count(),
                'arrived_count' => $rows->where('lifecycle_key', 'arrived')->count(),
                'informed_count' => $rows->where('lifecycle_key', 'customer_informed')->count(),
                'collected_count' => $rows->whereIn('lifecycle_key', ['collected', 'delivered'])->count(),
            ],
        ];
    }

    private function decoratePurchasedHistoryRow(object $row): object
    {
        $arrivedQty = max(0, (int) ($row->arrived_qty ?? 0));
        $informedCount = max(0, (int) ($row->informed_count ?? 0));
        $readyQty = max(0, (int) ($row->ready_qty ?? 0));
        $collectedQty = max(0, (int) ($row->collected_qty ?? 0));
        $deliveredQty = max(0, (int) ($row->delivered_qty ?? 0));
        $qty = max(0, (int) ($row->qty ?? 0));
        $status = strtolower((string) ($row->latest_arrival_status ?? ''));

        $key = 'pending_arrival';
        $label = 'Pending arrival';
        $classes = 'bg-amber-50 text-amber-700 ring-amber-100';

        if ($deliveredQty > 0 || $status === 'delivered') {
            $key = 'delivered';
            $label = ($qty > 0 && $deliveredQty > 0 && $deliveredQty < $qty) ? 'Partially delivered' : 'Delivered';
            $classes = 'bg-slate-100 text-slate-700 ring-slate-200';
        } elseif ($collectedQty > 0 || $status === 'collected') {
            $key = 'collected';
            $label = ($qty > 0 && $collectedQty > 0 && $collectedQty < $qty) ? 'Partially collected' : 'Collected';
            $classes = 'bg-slate-100 text-slate-700 ring-slate-200';
        } elseif ($informedCount > 0 || ! empty($row->latest_informed_at)) {
            $key = 'customer_informed';
            $label = 'Customer informed';
            $classes = 'bg-indigo-50 text-indigo-700 ring-indigo-100';
        } elseif ($readyQty > 0 || in_array($status, ['ready_for_collection', 'for_delivery'], true)) {
            $key = 'ready_for_collection';
            $label = 'Ready for collection';
            $classes = 'bg-sky-50 text-sky-700 ring-sky-100';
        } elseif ($arrivedQty > 0 || in_array($status, ['arrived', 'pending_customs_clearance'], true)) {
            $key = 'arrived';
            $label = ($qty > 0 && $arrivedQty > 0 && $arrivedQty < $qty) ? 'Partially arrived' : 'Arrived';
            $classes = 'bg-emerald-50 text-emerald-700 ring-emerald-100';
        }

        $row->customer_display_name = trim((string) ($row->bill_to_name ?: $row->bill_to_company)) ?: 'Unknown customer';
        $row->item_display_name = trim((string) ($row->item_name ?: $row->description)) ?: 'Unnamed item';
        $row->lifecycle_key = $key;
        $row->lifecycle_label = $label;
        $row->lifecycle_badge_classes = $classes;
        $row->purchased_at_display = $row->ordered_at ?: $row->created_at;
        $row->arrival_remaining_qty = max(0, $qty - $arrivedQty);
        $row->arrived_qty = $arrivedQty;
        $row->collected_or_delivered_qty = $collectedQty + $deliveredQty;

        return $row;
    }


    public function recordPurchase(int $orderId, int $itemId, array $data, ?int $userId = null): array
    {
        return DB::transaction(function () use ($orderId, $itemId, $data, $userId) {
            $item = $this->itemRowsForOrder($orderId, '', 'all')
                ->first(fn ($row) => (int) $row->item_id === $itemId);

            if (! $item) {
                throw ValidationException::withMessages([
                    'item' => 'This item was not found on the selected order.',
                ]);
            }

            $maxQty = max(0, (int) $item->remaining_to_buy_qty + (int) $item->active_pre_purchase_issue_qty);
            $qty = (int) ($data['qty'] ?? 0);

            if ($maxQty < 1) {
                throw ValidationException::withMessages([
                    'qty' => 'There is no remaining or problem quantity available to purchase for this item.',
                ]);
            }

            if ($qty > $maxQty) {
                throw ValidationException::withMessages([
                    'qty' => 'Quantity cannot exceed the outstanding purchasable quantity of ' . $maxQty . '.',
                ]);
            }

            $unitPrice = round((float) ($data['purchase_unit_price'] ?? 0), 2);
            $lineTotal = round($unitPrice * $qty, 2);
            $orderedAt = ! empty($data['ordered_at']) ? $data['ordered_at'] : now()->toDateString();
            $rootItemId = (int) ($item->lineage_root_id ?: $item->item_id);

            $purchaseId = DB::table('order_item_purchases')->insertGetId([
                'order_item_id' => $itemId,
                'root_item_id' => $rootItemId,
                'order_id' => $orderId,
                'order_retailer_id' => $item->order_retailer_id,
                'retailer_id' => ! empty($data['supplier_retailer_id']) ? (int) $data['supplier_retailer_id'] : $item->retailer_id,
                'qty' => $qty,
                'status' => 'purchased',
                'purchase_unit_price' => $unitPrice,
                'purchase_line_total' => $lineTotal,
                'estimated_retailer_delivery_date' => ! empty($data['estimated_retailer_delivery_date']) ? $data['estimated_retailer_delivery_date'] : null,
                'currency' => 'GBP',
                'marketplace_seller' => trim((string) ($data['marketplace_seller'] ?? '')) ?: ($item->marketplace_seller ?: null),
                'retailer_order_reference' => trim((string) ($data['retailer_order_reference'] ?? '')) ?: null,
                'note' => trim((string) ($data['note'] ?? '')) ?: null,
                'ordered_at' => $orderedAt,
                'resolution_status' => null,
                'created_by_user_id' => $userId,
                'updated_by_user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $openIssueIds = DB::table('purchase_issues')
                ->where('root_item_id', $rootItemId)
                ->where('issue_stage', 'pre_purchase')
                ->whereIn('status', ['open', 'pending', 'awaiting_customer'])
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $resolvedIssues = 0;

            if (! empty($openIssueIds)) {
                $resolutionNote = 'Automatically resolved because purchase event #' . $purchaseId . ' was recorded for this item.';

                $resolvedIssues = DB::table('purchase_issues')
                    ->whereIn('id', $openIssueIds)
                    ->update([
                        'status' => 'resolved',
                        'resolution_type' => 'purchased_successfully',
                        'resolution_notes' => DB::raw("TRIM(CONCAT(COALESCE(resolution_notes, ''), CASE WHEN COALESCE(resolution_notes, '') = '' THEN '' ELSE '\\n' END, " . DB::getPdo()->quote($resolutionNote) . "))"),
                        'resolved_at' => now(),
                        'resolved_by_user_id' => $userId,
                        'updated_by_user_id' => $userId,
                        'updated_at' => now(),
                    ]);

                foreach ($openIssueIds as $issueId) {
                    $this->writePurchaseDeskEvent('purchase_issue', $issueId, 'pre_purchase_issue_resolved_by_purchase', [
                        'order_id' => $orderId,
                        'order_item_id' => $itemId,
                        'root_item_id' => $rootItemId,
                        'purchase_event_id' => $purchaseId,
                        'qty' => $qty,
                    ], $userId);

                    $this->writeActivityLog('purchase_issue', $issueId, 'Pre-purchase issue resolved', $resolutionNote, $userId);
                }
            }

            $this->writePurchaseDeskEvent('order_item_purchase', $purchaseId, 'purchase_recorded_v2', [
                'order_id' => $orderId,
                'order_item_id' => $itemId,
                'root_item_id' => $rootItemId,
                'qty' => $qty,
                'purchase_unit_price' => $unitPrice,
                'purchase_line_total' => $lineTotal,
                'retailer_order_reference' => trim((string) ($data['retailer_order_reference'] ?? '')) ?: null,
                'resolved_issue_ids' => $openIssueIds,
            ], $userId);

            return [
                'purchase_id' => $purchaseId,
                'resolved_issues' => (int) $resolvedIssues,
            ];
        });
    }


    public function recordPurchaseBasket(int $orderId, array $data, ?int $userId = null): array
    {
        $lines = collect($data['lines'] ?? [])
            ->filter(fn ($line) => ! empty($line['selected']))
            ->mapWithKeys(fn ($line, $itemId) => [(int) $itemId => $line]);

        if ($lines->isEmpty()) {
            throw ValidationException::withMessages([
                'lines' => 'Select at least one item line to record a purchase.',
            ]);
        }

        return DB::transaction(function () use ($orderId, $data, $userId, $lines) {
            $created = 0;
            $resolvedIssues = 0;
            $purchaseIds = [];

            foreach ($lines as $itemId => $line) {
                $lineData = [
                    'qty' => $line['qty'] ?? null,
                    'purchase_unit_price' => $line['purchase_unit_price'] ?? null,
                    'ordered_at' => $data['ordered_at'] ?? null,
                    'estimated_retailer_delivery_date' => $data['estimated_retailer_delivery_date'] ?? null,
                    'retailer_order_reference' => $data['retailer_order_reference'] ?? null,
                    'marketplace_seller' => $data['marketplace_seller'] ?? null,
                    'supplier_retailer_id' => $data['supplier_retailer_id'] ?? null,
                    'note' => $data['note'] ?? null,
                ];

                $result = $this->recordPurchase($orderId, (int) $itemId, $lineData, $userId);
                $created++;
                $resolvedIssues += (int) ($result['resolved_issues'] ?? 0);
                $purchaseIds[] = (int) ($result['purchase_id'] ?? 0);
            }

            $this->writePurchaseDeskEvent('order', $orderId, 'purchase_basket_recorded_v2', [
                'line_count' => $created,
                'purchase_ids' => array_values(array_filter($purchaseIds)),
                'retailer_order_reference' => trim((string) ($data['retailer_order_reference'] ?? '')) ?: null,
                'supplier_retailer_id' => ! empty($data['supplier_retailer_id']) ? (int) $data['supplier_retailer_id'] : null,
            ], $userId);

            return [
                'created' => $created,
                'resolved_issues' => $resolvedIssues,
            ];
        });
    }

    public function updatePurchaseBatch(int $orderId, array $data, ?int $userId = null): array
    {
        $purchaseIds = collect($data['purchase_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($purchaseIds->isEmpty()) {
            throw ValidationException::withMessages([
                'purchase_ids' => 'No purchase lines were selected for editing.',
            ]);
        }

        return DB::transaction(function () use ($orderId, $data, $userId, $purchaseIds) {
            $existing = DB::table('order_item_purchases')
                ->where('order_id', $orderId)
                ->whereIn('id', $purchaseIds->all())
                ->whereNull('cancelled_at')
                ->get();

            if ($existing->count() !== $purchaseIds->count()) {
                throw ValidationException::withMessages([
                    'purchase_ids' => 'One or more purchase lines could not be found for this order.',
                ]);
            }

            $orderedAt = ! empty($data['ordered_at']) ? \Carbon\Carbon::parse($data['ordered_at'])->toDateString() : null;
            $eta = ! empty($data['estimated_retailer_delivery_date']) ? \Carbon\Carbon::parse($data['estimated_retailer_delivery_date'])->toDateString() : null;
            $reference = trim((string) ($data['retailer_order_reference'] ?? '')) ?: null;
            $seller = trim((string) ($data['marketplace_seller'] ?? '')) ?: null;
            $note = trim((string) ($data['note'] ?? '')) ?: null;

            $updates = [
                'retailer_id' => (int) $data['supplier_retailer_id'],
                'retailer_order_reference' => $reference,
                'estimated_retailer_delivery_date' => $eta,
                'marketplace_seller' => $seller,
                'note' => $note,
                'updated_by_user_id' => $userId,
                'updated_at' => now(),
            ];

            if ($orderedAt !== null) {
                $updates['ordered_at'] = $orderedAt;
            }

            $updated = DB::table('order_item_purchases')
                ->where('order_id', $orderId)
                ->whereIn('id', $purchaseIds->all())
                ->whereNull('cancelled_at')
                ->update($updates);

            $this->writePurchaseDeskEvent('order', $orderId, 'purchase_batch_metadata_updated_v2', [
                'purchase_ids' => $purchaseIds->all(),
                'supplier_retailer_id' => (int) $data['supplier_retailer_id'],
                'retailer_order_reference' => $reference,
                'ordered_at' => $orderedAt,
                'estimated_retailer_delivery_date' => $eta,
                'marketplace_seller' => $seller,
            ], $userId);

            return [
                'updated' => (int) $updated,
            ];
        });
    }



    public function updatePurchaseLine(int $orderId, int $purchaseId, array $data, ?int $userId = null): array
    {
        return DB::transaction(function () use ($orderId, $purchaseId, $data, $userId) {
            $row = DB::table('order_item_purchases')
                ->where('order_id', $orderId)
                ->where('id', $purchaseId)
                ->lockForUpdate()
                ->first();

            if (! $row) {
                throw ValidationException::withMessages([
                    'purchase_line' => 'Purchase line could not be found for this order.',
                ]);
            }

            if ($row->cancelled_at !== null) {
                throw ValidationException::withMessages([
                    'purchase_line' => 'This purchase line has already been undone and cannot be edited.',
                ]);
            }

            if (! in_array((string) $row->status, ['purchased', 'ordered', 'received'], true)) {
                throw ValidationException::withMessages([
                    'purchase_line' => 'Only active purchased lines can be edited from this screen.',
                ]);
            }

            $hasArrival = DB::table('purchase_arrival_assignments')
                ->where('order_item_purchase_id', $purchaseId)
                ->whereNull('undone_at')
                ->exists();

            if ($hasArrival) {
                throw ValidationException::withMessages([
                    'purchase_line' => 'This purchase line has an active arrival assignment. Undo the arrival assignment before editing quantity or price.',
                ]);
            }

            $qty = (int) ($data['qty'] ?? 0);
            $unitPrice = round((float) ($data['purchase_unit_price'] ?? -1), 2);

            if ($qty < 1) {
                throw ValidationException::withMessages([
                    'qty' => 'Quantity must be at least 1.',
                ]);
            }

            if ($unitPrice < 0) {
                throw ValidationException::withMessages([
                    'purchase_unit_price' => 'Purchase price cannot be negative.',
                ]);
            }

            $rootItem = DB::table('order_items')
                ->where('id', (int) $row->root_item_id)
                ->orWhere('id', (int) $row->order_item_id)
                ->orderByRaw('id = ? desc', [(int) $row->root_item_id])
                ->first();

            $requestedQty = max(1, (int) ($rootItem->quantity ?? 1));

            $otherActiveQty = (int) DB::table('order_item_purchases')
                ->where('root_item_id', (int) $row->root_item_id)
                ->whereIn('status', ['purchased', 'ordered', 'received'])
                ->whereNull('cancelled_at')
                ->where('id', '<>', $purchaseId)
                ->sum('qty');

            $maxAllowedQty = max(1, $requestedQty - $otherActiveQty);

            if ($qty > $maxAllowedQty) {
                throw ValidationException::withMessages([
                    'qty' => 'Quantity cannot exceed the remaining requested quantity for this item. Maximum allowed here is ' . $maxAllowedQty . '.',
                ]);
            }

            $lineTotal = round($qty * $unitPrice, 2);
            $oldQty = (int) $row->qty;
            $oldUnitPrice = $row->purchase_unit_price !== null ? (float) $row->purchase_unit_price : null;
            $oldLineTotal = $row->purchase_line_total !== null ? (float) $row->purchase_line_total : null;

            DB::table('order_item_purchases')
                ->where('id', $purchaseId)
                ->update([
                    'qty' => $qty,
                    'purchase_unit_price' => $unitPrice,
                    'purchase_line_total' => $lineTotal,
                    'updated_by_user_id' => $userId,
                    'updated_at' => now(),
                ]);

            $activeQty = (int) DB::table('order_item_purchases')
                ->where('root_item_id', (int) $row->root_item_id)
                ->whereIn('status', ['purchased', 'ordered', 'received'])
                ->whereNull('cancelled_at')
                ->sum('qty');

            DB::table('order_items')
                ->where('id', (int) $row->order_item_id)
                ->update([
                    'status' => $activeQty > 0 ? 'purchased' : 'requested',
                    'purchase_price' => $unitPrice,
                    'updated_by_user_id' => $userId,
                    'updated_at' => now(),
                ]);

            $this->writePurchaseDeskEvent('order_item_purchase', $purchaseId, 'purchase_line_updated_v2', [
                'order_id' => $orderId,
                'order_item_id' => (int) $row->order_item_id,
                'root_item_id' => (int) $row->root_item_id,
                'old_qty' => $oldQty,
                'new_qty' => $qty,
                'old_purchase_unit_price' => $oldUnitPrice,
                'new_purchase_unit_price' => $unitPrice,
                'old_purchase_line_total' => $oldLineTotal,
                'new_purchase_line_total' => $lineTotal,
            ], $userId);

            $this->writeActivityLog(
                'order',
                $orderId,
                'Purchase line updated',
                'Purchase #' . $purchaseId . ' was updated from qty ' . $oldQty . ' / ' . ($oldUnitPrice !== null ? number_format($oldUnitPrice, 2) : '—') . ' to qty ' . $qty . ' / ' . number_format($unitPrice, 2) . '.',
                $userId
            );

            return [
                'updated' => 1,
                'qty' => $qty,
                'purchase_unit_price' => $unitPrice,
                'purchase_line_total' => $lineTotal,
            ];
        });
    }

    public function undoPurchaseBatch(int $orderId, array $data, ?int $userId = null): array
    {
        $purchaseIds = collect($data['purchase_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($purchaseIds->isEmpty()) {
            throw ValidationException::withMessages([
                'purchase_ids' => 'No purchase lines were selected for undo.',
            ]);
        }

        return DB::transaction(function () use ($orderId, $purchaseIds, $data, $userId) {
            $undone = 0;
            $reason = trim((string) ($data['reason'] ?? ''));

            foreach ($purchaseIds as $purchaseId) {
                $this->undoPurchaseLineInsideTransaction($orderId, (int) $purchaseId, $reason, $userId);
                $undone++;
            }

            $this->writePurchaseDeskEvent('order', $orderId, 'purchase_batch_undone_v2', [
                'purchase_ids' => $purchaseIds->all(),
                'reason' => $reason,
                'undone_count' => $undone,
            ], $userId);

            $this->writeActivityLog('order', $orderId, 'Purchase batch undone', $undone . ' purchase line' . ($undone === 1 ? '' : 's') . ' were undone. Reason: ' . $reason, $userId);

            return [
                'undone' => $undone,
            ];
        });
    }

    public function undoPurchaseLine(int $orderId, int $purchaseId, string $reason, ?int $userId = null): array
    {
        return DB::transaction(function () use ($orderId, $purchaseId, $reason, $userId) {
            $this->undoPurchaseLineInsideTransaction($orderId, $purchaseId, trim($reason), $userId);

            return [
                'undone' => 1,
            ];
        });
    }

    private function undoPurchaseLineInsideTransaction(int $orderId, int $purchaseId, string $reason, ?int $userId = null): void
    {
        if ($reason === '') {
            throw ValidationException::withMessages([
                'reason' => 'Please enter a reason for undoing this purchase.',
            ]);
        }

        $row = DB::table('order_item_purchases')
            ->where('order_id', $orderId)
            ->where('id', $purchaseId)
            ->lockForUpdate()
            ->first();

        if (! $row) {
            throw ValidationException::withMessages([
                'purchase_ids' => 'One or more purchase lines could not be found for this order.',
            ]);
        }

        if ($row->cancelled_at !== null) {
            return;
        }

        if (! in_array((string) $row->status, ['purchased', 'ordered', 'received'], true)) {
            throw ValidationException::withMessages([
                'purchase_ids' => 'Only purchased lines can be undone from this screen.',
            ]);
        }

        $hasArrival = DB::table('purchase_arrival_assignments')
            ->where('order_item_purchase_id', $purchaseId)
            ->whereNull('undone_at')
            ->exists();

        if ($hasArrival) {
            throw ValidationException::withMessages([
                'undo' => 'This purchase line has an active arrival assignment. Undo the arrival assignment before undoing the purchase.',
            ]);
        }

        $noteAppend = "\nPurchase line undone " . now()->format('Y-m-d H:i') . ' by user #' . ($userId ?: 'unknown') . ': ' . $reason;

        DB::table('order_item_purchases')
            ->where('id', $purchaseId)
            ->update([
                'cancelled_at' => now(),
                'internal_notes' => trim((string) ($row->internal_notes ?? '') . $noteAppend),
                'updated_by_user_id' => $userId,
                'updated_at' => now(),
            ]);

        $reversalId = DB::table('order_item_purchases')->insertGetId([
            'order_item_id' => (int) $row->order_item_id,
            'root_item_id' => (int) $row->root_item_id,
            'order_id' => (int) $row->order_id,
            'order_retailer_id' => $row->order_retailer_id,
            'retailer_id' => $row->retailer_id,
            'qty' => (int) $row->qty,
            'status' => 'purchase_undo',
            'reversal_of_purchase_id' => $purchaseId,
            'purchase_unit_price' => $row->purchase_unit_price,
            'purchase_line_total' => $row->purchase_line_total !== null ? -abs((float) $row->purchase_line_total) : null,
            'estimated_retailer_delivery_date' => $row->estimated_retailer_delivery_date,
            'currency' => $row->currency ?: 'GBP',
            'marketplace_seller' => $row->marketplace_seller,
            'retailer_order_reference' => $row->retailer_order_reference,
            'note' => $row->note,
            'ordered_at' => $row->ordered_at,
            'internal_notes' => 'Reversal event for purchase #' . $purchaseId . '. Reason: ' . $reason,
            'created_by_user_id' => $userId,
            'updated_by_user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $activeQty = (int) DB::table('order_item_purchases')
            ->where('root_item_id', $row->root_item_id)
            ->whereIn('status', ['purchased', 'ordered', 'received'])
            ->whereNull('cancelled_at')
            ->sum('qty');

        DB::table('order_items')
            ->where('id', $row->order_item_id)
            ->update([
                'status' => $activeQty > 0 ? 'purchased' : 'requested',
                'requires_inspection' => 0,
                'inspection_note' => null,
                'updated_by_user_id' => $userId,
                'updated_at' => now(),
            ]);

        $this->writePurchaseDeskEvent('order_item_purchase', $purchaseId, 'purchase_line_undone_v2', [
            'order_id' => $orderId,
            'order_item_id' => (int) $row->order_item_id,
            'root_item_id' => (int) $row->root_item_id,
            'qty' => (int) $row->qty,
            'reason' => $reason,
            'reversal_purchase_id' => $reversalId,
        ], $userId);

        $this->writeActivityLog('order', $orderId, 'Purchase line undone', 'Purchase #' . $purchaseId . ' was undone and returned to the buying list. Reason: ' . $reason, $userId);
    }


    public function createSupplier(array $data, ?int $userId = null): int
    {
        $result = app(RetailerRegistrationService::class)->register($data, $userId);

        return (int) $result['id'];
    }


    private function ensurePurchaseAttentionTableExists(): void
    {
        if (! Schema::hasTable('purchase_attention_flags')) {
            throw ValidationException::withMessages([
                'purple_attention' => 'Purple attention storage is not installed yet. Please run the Pass 2.15 migration, then try again.',
            ]);
        }
    }


    public function addItemAttention(int $orderId, int $itemId, array $data, ?int $userId = null): array
    {
        $this->ensurePurchaseAttentionTableExists();

        return DB::transaction(function () use ($orderId, $itemId, $data, $userId) {
            $item = DB::table('order_items')
                ->where('order_id', $orderId)
                ->where('id', $itemId)
                ->first();

            if (! $item) {
                throw ValidationException::withMessages([
                    'item' => 'This item could not be found on the selected order.',
                ]);
            }

            $type = $this->normaliseAttentionType((string) ($data['attention_type'] ?? ''));
            $note = trim((string) ($data['note'] ?? '')) ?: null;

            if ($type === 'other' && $note === null) {
                throw ValidationException::withMessages([
                    'note' => 'Please enter a purple note when using Other.',
                ]);
            }

            $rootItemId = (int) ($item->root_item_id ?: $item->id);

            $attentionId = DB::table('purchase_attention_flags')->insertGetId([
                'order_id' => $orderId,
                'order_item_id' => $itemId,
                'root_item_id' => $rootItemId,
                'order_item_purchase_id' => null,
                'attention_type' => $type,
                'note' => $note,
                'created_by_user_id' => $userId,
                'updated_by_user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $label = $this->attentionTypeLabel($type);

            $this->writePurchaseDeskEvent('purchase_attention', $attentionId, 'purple_attention_added_v2', [
                'order_id' => $orderId,
                'order_item_id' => $itemId,
                'root_item_id' => $rootItemId,
                'attention_type' => $type,
                'note' => $note,
            ], $userId);

            $this->writeActivityLog('order_item', $itemId, 'Purple attention added', $label . ($note ? ': ' . $note : ''), $userId);

            return ['attention_id' => $attentionId];
        });
    }

    public function addPurchaseAttention(int $orderId, int $purchaseId, array $data, ?int $userId = null): array
    {
        $this->ensurePurchaseAttentionTableExists();

        return DB::transaction(function () use ($orderId, $purchaseId, $data, $userId) {
            $purchase = DB::table('order_item_purchases')
                ->where('order_id', $orderId)
                ->where('id', $purchaseId)
                ->first();

            if (! $purchase) {
                throw ValidationException::withMessages([
                    'purchase_line' => 'This purchase line could not be found on the selected order.',
                ]);
            }

            if ($purchase->cancelled_at !== null) {
                throw ValidationException::withMessages([
                    'purchase_line' => 'Purple attention cannot be added to an undone purchase line.',
                ]);
            }

            $type = $this->normaliseAttentionType((string) ($data['attention_type'] ?? ''));
            $note = trim((string) ($data['note'] ?? '')) ?: null;

            if ($type === 'other' && $note === null) {
                throw ValidationException::withMessages([
                    'note' => 'Please enter a purple note when using Other.',
                ]);
            }

            $attentionId = DB::table('purchase_attention_flags')->insertGetId([
                'order_id' => $orderId,
                'order_item_id' => (int) $purchase->order_item_id,
                'root_item_id' => (int) $purchase->root_item_id,
                'order_item_purchase_id' => $purchaseId,
                'attention_type' => $type,
                'note' => $note,
                'created_by_user_id' => $userId,
                'updated_by_user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $label = $this->attentionTypeLabel($type);

            $this->writePurchaseDeskEvent('purchase_attention', $attentionId, 'purple_attention_added_v2', [
                'order_id' => $orderId,
                'order_item_purchase_id' => $purchaseId,
                'order_item_id' => (int) $purchase->order_item_id,
                'root_item_id' => (int) $purchase->root_item_id,
                'attention_type' => $type,
                'note' => $note,
            ], $userId);

            $this->writeActivityLog('order_item_purchase', $purchaseId, 'Purple attention added', $label . ($note ? ': ' . $note : ''), $userId);

            return ['attention_id' => $attentionId];
        });
    }

    public function clearAttention(int $orderId, int $attentionId, ?int $userId = null): void
    {
        $this->ensurePurchaseAttentionTableExists();

        DB::transaction(function () use ($orderId, $attentionId, $userId) {
            $attention = DB::table('purchase_attention_flags')
                ->where('order_id', $orderId)
                ->where('id', $attentionId)
                ->lockForUpdate()
                ->first();

            if (! $attention) {
                throw ValidationException::withMessages([
                    'attention' => 'Purple attention could not be found for this order.',
                ]);
            }

            if ($attention->cleared_at !== null) {
                throw ValidationException::withMessages([
                    'attention' => 'This purple attention has already been cleared.',
                ]);
            }

            DB::table('purchase_attention_flags')
                ->where('id', $attentionId)
                ->update([
                    'cleared_at' => now(),
                    'cleared_by_user_id' => $userId,
                    'updated_by_user_id' => $userId,
                    'updated_at' => now(),
                ]);

            $this->writePurchaseDeskEvent('purchase_attention', $attentionId, 'purple_attention_cleared_v2', [
                'order_id' => $orderId,
                'order_item_purchase_id' => $attention->order_item_purchase_id ? (int) $attention->order_item_purchase_id : null,
                'order_item_id' => $attention->order_item_id ? (int) $attention->order_item_id : null,
                'root_item_id' => (int) $attention->root_item_id,
                'attention_type' => $attention->attention_type,
            ], $userId);

            $subjectType = $attention->order_item_purchase_id ? 'order_item_purchase' : 'order_item';
            $subjectId = $attention->order_item_purchase_id ? (int) $attention->order_item_purchase_id : (int) $attention->order_item_id;
            $this->writeActivityLog($subjectType, $subjectId, 'Purple attention cleared', $this->attentionTypeLabel((string) $attention->attention_type), $userId);
        });
    }


    public function reportPrePurchaseProblem(int $orderId, int $itemId, array $data, ?int $userId = null): array
    {
        return DB::transaction(function () use ($orderId, $itemId, $data, $userId) {
            $item = $this->itemRowsForOrder($orderId, '', 'all')
                ->first(fn ($row) => (int) $row->item_id === $itemId);

            if (! $item) {
                throw ValidationException::withMessages(['item' => 'This item was not found on the selected order.']);
            }

            $remainingQty = max(0, (int) $item->remaining_to_buy_qty);
            if ($remainingQty < 1) {
                throw ValidationException::withMessages(['qty' => 'There is no remaining quantity available to move into a pre-purchase problem.']);
            }

            $affectedQty = (int) ($data['affected_qty'] ?? 0);
            if ($affectedQty < 1 || $affectedQty > $remainingQty) {
                throw ValidationException::withMessages(['affected_qty' => 'Problem quantity must be between 1 and ' . $remainingQty . '.']);
            }

            $type = $this->normalisePrePurchaseIssueType((string) ($data['issue_type'] ?? ''));
            $note = trim((string) ($data['notes'] ?? ''));

            if ($type === 'other' && mb_strlen($note) < 2) {
                throw ValidationException::withMessages(['notes' => 'Please enter a note when using Other.']);
            }

            $rootItemId = (int) ($item->lineage_root_id ?: $item->item_id);
            $status = $type === 'awaiting_customer_approval' ? 'awaiting_customer' : 'open';

            $issueId = DB::table('purchase_issues')->insertGetId([
                'order_item_id' => $itemId,
                'root_item_id' => $rootItemId,
                'order_id' => $orderId,
                'order_retailer_id' => $item->order_retailer_id,
                'retailer_id' => $item->retailer_id,
                'qty' => $affectedQty,
                'affected_qty' => $affectedQty,
                'issue_type' => $type,
                'issue_stage' => 'pre_purchase',
                'arrival_expectation' => 'expected',
                'severity' => 'medium',
                'status' => $status,
                'notes' => $note ?: null,
                'requires_customer_action' => $type === 'awaiting_customer_approval' ? 1 : 0,
                'created_by_user_id' => $userId,
                'updated_by_user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->writePurchaseDeskEvent('purchase_issue', $issueId, 'pre_purchase_issue_reported_v2', [
                'order_id' => $orderId,
                'order_item_id' => $itemId,
                'root_item_id' => $rootItemId,
                'issue_type' => $type,
                'affected_qty' => $affectedQty,
            ], $userId);

            $this->writeActivityLog('purchase_issue', $issueId, 'Pre-purchase problem reported', $this->prePurchaseIssueTypeLabel($type) . ($note ? ': ' . $note : ''), $userId);

            return ['issue_id' => $issueId];
        });
    }

    public function updatePrePurchaseProblem(int $orderId, int $issueId, array $data, ?int $userId = null): void
    {
        DB::transaction(function () use ($orderId, $issueId, $data, $userId) {
            $issue = DB::table('purchase_issues')
                ->where('id', $issueId)
                ->where('order_id', $orderId)
                ->where('issue_stage', 'pre_purchase')
                ->whereIn('status', ['open', 'awaiting_customer'])
                ->first();

            if (! $issue) {
                throw ValidationException::withMessages(['issue' => 'This active pre-purchase problem could not be found.']);
            }

            $item = $this->itemRowsForOrder($orderId, '', 'all')
                ->first(fn ($row) => (int) $row->item_id === (int) $issue->order_item_id);

            if (! $item) {
                throw ValidationException::withMessages(['item' => 'The item for this problem could not be found.']);
            }

            $currentQty = (int) ($issue->affected_qty ?: $issue->qty ?: 1);
            $maxQty = max(1, (int) $item->remaining_to_buy_qty + $currentQty);
            $affectedQty = (int) ($data['affected_qty'] ?? 0);
            if ($affectedQty < 1 || $affectedQty > $maxQty) {
                throw ValidationException::withMessages(['affected_qty' => 'Problem quantity must be between 1 and ' . $maxQty . '.']);
            }

            $type = $this->normalisePrePurchaseIssueType((string) ($data['issue_type'] ?? ''));
            $note = trim((string) ($data['notes'] ?? ''));

            if ($type === 'other' && mb_strlen($note) < 2) {
                throw ValidationException::withMessages(['notes' => 'Please enter a note when using Other.']);
            }

            DB::table('purchase_issues')
                ->where('id', $issueId)
                ->update([
                    'issue_type' => $type,
                    'qty' => $affectedQty,
                    'affected_qty' => $affectedQty,
                    'status' => $type === 'awaiting_customer_approval' ? 'awaiting_customer' : 'open',
                    'notes' => $note ?: null,
                    'requires_customer_action' => $type === 'awaiting_customer_approval' ? 1 : 0,
                    'updated_by_user_id' => $userId,
                    'updated_at' => now(),
                ]);

            $this->writePurchaseDeskEvent('purchase_issue', $issueId, 'pre_purchase_issue_updated_v2', [
                'order_id' => $orderId,
                'order_item_id' => (int) $issue->order_item_id,
                'root_item_id' => (int) $issue->root_item_id,
                'issue_type' => $type,
                'affected_qty' => $affectedQty,
            ], $userId);

            $this->writeActivityLog('purchase_issue', $issueId, 'Pre-purchase problem updated', $this->prePurchaseIssueTypeLabel($type) . ($note ? ': ' . $note : ''), $userId);
        });
    }

    public function resolvePrePurchaseProblem(int $orderId, int $issueId, array $data = [], ?int $userId = null): void
    {
        $this->closePrePurchaseProblem($orderId, $issueId, 'resolved', 'return_to_buy', (string) ($data['resolution_notes'] ?? ''), $userId, 'Pre-purchase problem resolved', 'pre_purchase_issue_resolved_v2');
    }

    public function cancelPrePurchaseProblem(int $orderId, int $issueId, array $data = [], ?int $userId = null): void
    {
        $this->closePrePurchaseProblem($orderId, $issueId, 'cancelled', 'reported_in_error', (string) ($data['resolution_notes'] ?? ''), $userId, 'Pre-purchase problem cancelled', 'pre_purchase_issue_cancelled_v2');
    }

    private function closePrePurchaseProblem(int $orderId, int $issueId, string $status, string $resolutionType, string $note, ?int $userId, string $title, string $eventType): void
    {
        DB::transaction(function () use ($orderId, $issueId, $status, $resolutionType, $note, $userId, $title, $eventType) {
            $issue = DB::table('purchase_issues')
                ->where('id', $issueId)
                ->where('order_id', $orderId)
                ->where('issue_stage', 'pre_purchase')
                ->whereIn('status', ['open', 'awaiting_customer'])
                ->first();

            if (! $issue) {
                throw ValidationException::withMessages(['issue' => 'This active pre-purchase problem could not be found.']);
            }

            $resolutionNote = trim($note) ?: ($resolutionType === 'return_to_buy' ? 'Resolved manually and returned to the buying list.' : 'Cancelled because it was reported by mistake.');

            DB::table('purchase_issues')
                ->where('id', $issueId)
                ->update([
                    'status' => $status,
                    'resolution_type' => $resolutionType,
                    'resolution_notes' => $resolutionNote,
                    'resolved_at' => now(),
                    'resolved_by_user_id' => $userId,
                    'updated_by_user_id' => $userId,
                    'updated_at' => now(),
                ]);

            $this->writePurchaseDeskEvent('purchase_issue', $issueId, $eventType, [
                'order_id' => $orderId,
                'order_item_id' => (int) $issue->order_item_id,
                'root_item_id' => (int) $issue->root_item_id,
                'resolution_type' => $resolutionType,
            ], $userId);

            $this->writeActivityLog('purchase_issue', $issueId, $title, $resolutionNote, $userId);
        });
    }

    public function orderWorkspace(int $orderId, array $filters = []): ?array
    {
        $order = DB::table('orders as o')
            ->leftJoin('users as u', 'u.id', '=', 'o.created_by_user_id')
            ->select(['o.*', 'u.name as operator_name'])
            ->where('o.id', $orderId)
            ->first();

        if (! $order) {
            return null;
        }

        $q = trim((string) ($filters['q'] ?? ''));
        $view = (string) ($filters['view'] ?? 'all');
        $isLivePurchasingOrder = $this->itemLifecycle()->isLivePurchasingOrder($orderId);

        $allItems = $isLivePurchasingOrder ? $this->itemRowsForOrder($orderId, '', 'all') : collect();
        $items = $isLivePurchasingOrder ? $this->itemRowsForOrder($orderId, $q, $view) : collect();
        $issues = $isLivePurchasingOrder ? $this->prePurchaseIssuesForOrder($orderId) : collect();
        $lineageOrderIds = $isLivePurchasingOrder ? $this->ancestorOrderIdsForOrderId($orderId) : [$orderId];
        $purchaseEvents = $isLivePurchasingOrder ? $this->purchaseEventsForOrderIds($lineageOrderIds) : collect();
        $attentionFlags = $this->activeAttentionFlagsForOrderIds($lineageOrderIds);
        $attentionByRoot = $attentionFlags->groupBy(fn ($flag) => (int) $flag->root_item_id);
        $attentionByPurchase = $attentionFlags
            ->filter(fn ($flag) => ! empty($flag->order_item_purchase_id))
            ->groupBy(fn ($flag) => (int) $flag->order_item_purchase_id);

        $purchaseEvents = $purchaseEvents->map(function ($event) use ($attentionByPurchase) {
            $event->active_attention_flags = $attentionByPurchase->get((int) $event->id, collect())->values();
            $event->active_attention_count = $event->active_attention_flags->count();
            return $event;
        });

        $purchaseEventsByRoot = $purchaseEvents->groupBy(fn ($event) => (int) $event->root_item_id);
        $issuesByRoot = $issues->groupBy(fn ($issue) => (int) $issue->root_item_id);
        $issuesByRetailerKey = $issues->groupBy(fn ($issue) => (string) ($issue->retailer_id ?? 0) . '|' . ($issue->retailer_name ?: 'Unknown retailer'));

        $retailers = $items
            ->groupBy(fn ($item) => (string) ($item->retailer_id ?? 0) . '|' . ($item->retailer_name ?: 'Unknown retailer'))
            ->map(function (Collection $rows) {
                $first = $rows->first();

                return [
                    'retailer_id' => $first->retailer_id,
                    'retailer_name' => $first->retailer_name ?: 'Unknown retailer',
                    'items' => $rows->values(),
                    'active_item_qty' => (int) $rows->sum('active_item_qty'),
                    'remaining_to_buy_qty' => (int) $rows->sum('remaining_to_buy_qty'),
                    'purchased_qty' => (int) $rows->sum('purchased_qty'),
                    'awaiting_arrival_qty' => (int) $rows->sum('awaiting_arrival_qty'),
                    'arrived_qty' => (int) $rows->sum('arrived_qty'),
                    'pre_purchase_problem_qty' => (int) $rows->sum('active_pre_purchase_issue_qty'),
                    'items_cost' => (float) $rows->sum('line_subtotal'),
                    'actionable_count' => (int) $rows->filter(fn ($item) => (int) $item->remaining_to_buy_qty > 0)->count(),
                    'purchase_batches' => collect(),
                    'purchase_batches_count' => 0,
                    'purchase_batches_line_count' => 0,
                    'purchase_batches_total' => 0.0,
                    'retailer_order_total' => 0.0,
                    'outstanding_line_count' => 0,
                    'outstanding_value' => 0.0,
                ];
            })
            ->sortBy('retailer_name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $retailers = $this->attachPurchaseBatchesToRetailers($retailers, $allItems, $purchaseEvents);

        $retailers = $retailers->map(function (array $retailer) use ($issuesByRetailerKey) {
            $key = (string) ($retailer['retailer_id'] ?? 0) . '|' . ($retailer['retailer_name'] ?: 'Unknown retailer');
            $problems = $issuesByRetailerKey->get($key, collect())->values();
            $retailer['pre_purchase_problems'] = $problems;
            $retailer['pre_purchase_problem_count'] = $problems->count();
            $retailer['pre_purchase_problem_qty'] = (int) $problems->sum(fn ($issue) => (int) ($issue->affected_qty ?: $issue->qty ?: 1));
            return $retailer;
        });

        $settlement = DB::query()->fromSub($this->settlementTotalsSubquery(), 'st')
            ->where('st.order_id', $orderId)
            ->first();

        return [
            'order' => $order,
            'items' => $items,
            'retailers' => $retailers,
            'issues' => $issues,
            'purchaseEvents' => $purchaseEvents,
            'purchaseEventsByRoot' => $purchaseEventsByRoot,
            'issuesByRoot' => $issuesByRoot,
            'attentionFlags' => $attentionFlags,
            'attentionByRoot' => $attentionByRoot,
            'attentionByPurchase' => $attentionByPurchase,
            'attentionTypeOptions' => $this->attentionTypeOptions(),
            'prePurchaseIssueTypeOptions' => $this->prePurchaseIssueTypeOptions(),
            'suppliers' => $this->activeSuppliers(),
            'filters' => [
                'q' => $q,
                'view' => $view,
            ],
            'isLivePurchasingOrder' => $isLivePurchasingOrder,
            'purchaseDisabledReason' => $isLivePurchasingOrder ? null : 'This order is not the current live purchasing version. Superseded, cancelled, refunded or replaced orders do not produce purchase requirements.',
            'summary' => [
                'items_cost' => (float) $allItems->sum('line_subtotal'),
                'active_item_qty' => (int) $allItems->sum('active_item_qty'),
                'remaining_to_buy_qty' => (int) $allItems->sum('remaining_to_buy_qty'),
                'purchased_qty' => (int) $allItems->sum('purchased_qty'),
                'awaiting_arrival_qty' => (int) $allItems->sum('awaiting_arrival_qty'),
                'arrived_qty' => (int) $allItems->sum('arrived_qty'),
                'pre_purchase_problem_qty' => (int) $allItems->sum('active_pre_purchase_issue_qty'),
                'settled_amount' => (float) ($settlement->settled_amount ?? 0),
                'balance_due' => max(0, (float) ($order->grand_total ?? 0) - (float) ($settlement->settled_amount ?? 0)),
            ],
        ];
    }

    private function itemRowsForOrder(int $orderId, string $q = '', string $view = 'all'): Collection
    {
        $lineageOrderIds = $this->ancestorOrderIdsForOrderId($orderId);

        $purchaseTotals = $this->purchaseTotalsSubqueryForOrderIds($lineageOrderIds);
        $arrivalTotals = $this->arrivalTotalsSubqueryForOrderIds($lineageOrderIds);
        $issueTotals = $this->issueTotalsSubqueryForOrderIds($lineageOrderIds);

        $query = DB::table('order_items as oi')
            ->leftJoin('order_retailers as ore', 'ore.id', '=', 'oi.order_retailer_id')
            ->leftJoin('retailers as r', 'r.id', '=', 'ore.retailer_id')
            ->leftJoinSub($purchaseTotals, 'pt', function ($join) {
                $join->on('pt.root_item_id', '=', DB::raw('COALESCE(oi.root_item_id, oi.id)'));
            })
            ->leftJoinSub($arrivalTotals, 'at', function ($join) {
                $join->on('at.root_item_id', '=', DB::raw('COALESCE(oi.root_item_id, oi.id)'));
            })
            ->leftJoinSub($issueTotals, 'pit', function ($join) {
                $join->on('pit.root_item_id', '=', DB::raw('COALESCE(oi.root_item_id, oi.id)'));
            })
            ->select([
                'oi.id as item_id',
                'oi.order_id',
                'oi.order_retailer_id',
                'oi.root_item_id',
                'oi.item_name',
                'oi.description',
                'oi.product_code',
                'oi.product_url',
                'oi.marketplace_seller',
                'oi.quantity',
                'oi.unit_price',
                'oi.line_subtotal',
                'oi.line_total',
                'oi.item_retailer_delivery_fee',
                'oi.retailer_delivery_allocated',
                'oi.status as item_status',
                'oi.requires_inspection',
                'oi.inspection_note',
                'ore.retailer_id',
                DB::raw('COALESCE(r.name, ore.retailer_name, "Unknown retailer") as retailer_name'),
                DB::raw('COALESCE(oi.root_item_id, oi.id) as lineage_root_id'),
                DB::raw($this->itemLifecycle()->activeItemQtyExpression('oi') . ' as active_item_qty'),
                DB::raw('COALESCE(pt.gross_purchased_qty, 0) as gross_purchased_qty'),
                DB::raw('GREATEST(0, COALESCE(pt.gross_purchased_qty, 0) - COALESCE(pit.return_to_buy_issue_qty, 0)) as purchased_qty'),
                DB::raw('COALESCE(pt.terminal_problem_qty, 0) as terminal_problem_qty'),
                DB::raw('COALESCE(pit.active_issue_qty, 0) as active_issue_qty'),
                DB::raw('COALESCE(pit.active_pre_purchase_issue_qty, 0) as active_pre_purchase_issue_qty'),
                DB::raw('COALESCE(pit.resolved_terminal_issue_qty, 0) as resolved_terminal_issue_qty'),
                DB::raw('COALESCE(at.arrived_qty, 0) as arrived_qty'),
                DB::raw('GREATEST(0, (' . $this->itemLifecycle()->activeItemQtyExpression('oi') . ') - GREATEST(0, COALESCE(pt.gross_purchased_qty, 0) - COALESCE(pit.return_to_buy_issue_qty, 0)) - COALESCE(pt.terminal_problem_qty, 0) - COALESCE(pit.active_issue_qty, 0) - COALESCE(pit.resolved_terminal_issue_qty, 0)) as remaining_to_buy_qty'),
                DB::raw('GREATEST(0, GREATEST(0, COALESCE(pt.gross_purchased_qty, 0) - COALESCE(pit.return_to_buy_issue_qty, 0)) - COALESCE(pt.terminal_problem_qty, 0) - COALESCE(at.arrived_qty, 0)) as awaiting_arrival_qty'),
            ])
            ->where('oi.order_id', $orderId);

        $this->itemLifecycle()->applyPurchasableItemConstraints($query, 'oi');

        if ($q !== '') {
            $needle = '%' . mb_strtolower($q) . '%';
            $query->where(function ($search) use ($needle) {
                $search->whereRaw('LOWER(COALESCE(oi.item_name, "")) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(COALESCE(oi.description, "")) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(COALESCE(oi.product_code, "")) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(COALESCE(oi.product_url, "")) LIKE ?', [$needle]);
            });
        }

        $rows = $query
            ->orderBy('retailer_name')
            ->orderBy('oi.sort_order')
            ->orderBy('oi.id')
            ->get();

        if ($view === 'actionable') {
            $rows = $rows->filter(fn ($item) => (int) $item->remaining_to_buy_qty > 0 || (int) $item->active_pre_purchase_issue_qty > 0)->values();
        } elseif ($view === 'to_buy') {
            $rows = $rows->filter(fn ($item) => (int) $item->remaining_to_buy_qty > 0)->values();
        } elseif ($view === 'problems') {
            $rows = $rows->filter(fn ($item) => (int) $item->active_pre_purchase_issue_qty > 0)->values();
        } elseif ($view === 'purchased') {
            $rows = $rows->filter(fn ($item) => (int) $item->purchased_qty > 0 && (int) $item->remaining_to_buy_qty <= 0 && (int) $item->active_pre_purchase_issue_qty <= 0)->values();
        }

        return $rows;
    }


    private function purchaseEventsForOrderIds(array $orderIds = []): Collection
    {
        $editEvents = DB::table('order_events')
            ->selectRaw('entity_id as purchase_id')
            ->selectRaw('COUNT(*) as edit_count')
            ->selectRaw('MAX(created_at) as latest_edit_at')
            ->where('entity_type', 'order_item_purchase')
            ->where('event_type', 'purchase_line_updated_v2')
            ->groupBy('entity_id');

        $arrivalAssignments = DB::table('purchase_arrival_assignments')
            ->selectRaw('order_item_purchase_id')
            ->selectRaw('SUM(CASE WHEN undone_at IS NULL THEN qty ELSE 0 END) as active_arrival_qty')
            ->whereNull('undone_at')
            ->groupBy('order_item_purchase_id');

        $query = DB::table('order_item_purchases as oip')
            ->leftJoin('retailers as r', 'r.id', '=', 'oip.retailer_id')
            ->leftJoin('order_items as oi', 'oi.id', '=', 'oip.order_item_id')
            ->leftJoin('users as u', 'u.id', '=', 'oip.created_by_user_id')
            ->leftJoinSub($editEvents, 'pe', fn ($join) => $join->on('pe.purchase_id', '=', 'oip.id'))
            ->leftJoinSub($arrivalAssignments, 'paa', fn ($join) => $join->on('paa.order_item_purchase_id', '=', 'oip.id'))
            ->select([
                'oip.id',
                'oip.order_id',
                'oip.order_item_id',
                'oip.root_item_id',
                'oip.qty',
                'oip.status',
                'oip.purchase_unit_price',
                'oip.purchase_line_total',
                'oip.currency',
                'oip.retailer_id',
                'oip.retailer_order_reference',
                'oip.marketplace_seller',
                'oip.ordered_at',
                'oip.estimated_retailer_delivery_date',
                'oip.problem_code',
                'oip.problem_notes',
                'oip.cancelled_at',
                'oip.resolution_status',
                'oip.resolution_action',
                'oip.note',
                'oip.created_at',
                DB::raw('COALESCE(pe.edit_count, 0) as edit_count'),
                'pe.latest_edit_at',
                DB::raw('COALESCE(paa.active_arrival_qty, 0) as active_arrival_qty'),
                DB::raw('COALESCE(r.name, "Unknown retailer") as retailer_name'),
                'oi.item_name',
                'oi.product_code',
                'oi.product_url',
                'u.name as created_by_name',
            ]);

        if (! empty($orderIds)) {
            $query->whereIn('oip.order_id', $orderIds);
        }

        return $query
            ->orderByDesc(DB::raw('COALESCE(oip.ordered_at, oip.created_at)'))
            ->orderByDesc('oip.id')
            ->get();
    }

    private function attachPurchaseBatchesToRetailers(Collection $retailers, Collection $allItems, Collection $purchaseEvents): Collection
    {
        $rootsByRetailerKey = $allItems
            ->groupBy(fn ($item) => (string) ($item->retailer_id ?? 0) . '|' . ($item->retailer_name ?: 'Unknown retailer'))
            ->map(fn (Collection $rows) => $rows->map(fn ($row) => (int) $row->lineage_root_id)->unique()->values());

        return $retailers->map(function (array $retailer) use ($rootsByRetailerKey, $purchaseEvents, $allItems) {
            $key = (string) ($retailer['retailer_id'] ?? 0) . '|' . ($retailer['retailer_name'] ?: 'Unknown retailer');
            $retailerRows = $allItems->filter(fn ($item) => ((string) ($item->retailer_id ?? 0) . '|' . ($item->retailer_name ?: 'Unknown retailer')) === $key)->values();
            $rootIds = $rootsByRetailerKey->get($key, collect())->all();

            $retailer['retailer_order_total'] = (float) $retailerRows->sum(fn ($item) => (float) ($item->line_subtotal ?: $item->line_total));
            $retailer['outstanding_line_count'] = (int) $retailerRows->filter(fn ($item) => (int) $item->remaining_to_buy_qty > 0)->count();
            $retailer['outstanding_value'] = (float) $retailerRows->sum(function ($item) {
                $remainingQty = max(0, (int) $item->remaining_to_buy_qty);
                return $remainingQty * (float) $item->unit_price;
            });

            $events = $purchaseEvents
                ->filter(fn ($event) => in_array((int) $event->root_item_id, $rootIds, true))
                ->filter(fn ($event) => in_array((string) $event->status, ['purchased', 'ordered', 'received'], true))
                ->values();

            $batches = $events
                ->groupBy(function ($event) {
                    $date = $event->ordered_at ?: $event->created_at;
                    $dateKey = $date ? \Carbon\Carbon::parse($date)->toDateString() : 'unknown-date';
                    $supplierKey = (string) ($event->retailer_id ?? 0);
                    $referenceKey = mb_strtolower(trim((string) ($event->retailer_order_reference ?? 'no-reference')));

                    return $dateKey . '|' . $supplierKey . '|' . $referenceKey;
                })
                ->map(function (Collection $lines) {
                    $first = $lines->first();
                    $date = $first->ordered_at ?: $first->created_at;
                    $timeAt = $first->created_at ?: $first->ordered_at;
                    $activeLines = $lines->filter(fn ($line) => $line->cancelled_at === null)->values();
                    $undoneLines = $lines->filter(fn ($line) => $line->cancelled_at !== null)->values();
                    $editedLines = $lines->filter(fn ($line) => (int) ($line->edit_count ?? 0) > 0)->values();

                    return [
                        'date' => $date,
                        'time_at' => $timeAt,
                        'purchase_ids' => $activeLines->pluck('id')->map(fn ($id) => (int) $id)->values(),
                        'all_purchase_ids' => $lines->pluck('id')->map(fn ($id) => (int) $id)->values(),
                        'supplier_retailer_id' => $first->retailer_id ?: null,
                        'supplier_name' => $first->retailer_name ?: 'Unknown supplier',
                        'retailer_order_reference' => $first->retailer_order_reference ?: null,
                        'marketplace_seller' => $first->marketplace_seller ?: null,
                        'note' => $first->note ?: null,
                        'eta' => $lines->pluck('estimated_retailer_delivery_date')->filter()->sort()->first(),
                        'qty' => (int) $activeLines->sum('qty'),
                        'line_count' => $activeLines->count(),
                        'total' => (float) $activeLines->sum('purchase_line_total'),
                        'original_qty' => (int) $lines->sum('qty'),
                        'original_line_count' => $lines->count(),
                        'original_total' => (float) $lines->sum('purchase_line_total'),
                        'undone_qty' => (int) $undoneLines->sum('qty'),
                        'undone_line_count' => $undoneLines->count(),
                        'undone_total' => (float) $undoneLines->sum('purchase_line_total'),
                        'edited_line_count' => $editedLines->count(),
                        'latest_edit_at' => $editedLines->max(fn ($line) => $line->latest_edit_at),
                        'created_by_name' => $first->created_by_name ?: null,
                        'latest_at' => $lines->max(fn ($line) => $line->ordered_at ?: $line->created_at),
                        'lines' => $lines->sortBy([
                            fn ($a, $b) => ($a->cancelled_at !== null) <=> ($b->cancelled_at !== null),
                            fn ($a, $b) => strnatcasecmp((string) $a->item_name, (string) $b->item_name),
                        ])->values(),
                    ];
                })
                ->sortByDesc('latest_at')
                ->values();

            $retailer['purchase_batches'] = $batches;
            $retailer['purchase_batches_count'] = $batches->count();
            $retailer['purchase_batches_line_count'] = (int) $batches->sum('line_count');
            $retailer['purchase_batches_total'] = (float) $batches->sum('total');
            $retailer['purchase_batches_undone_line_count'] = (int) $batches->sum('undone_line_count');
            $retailer['purchase_batches_edited_line_count'] = (int) $batches->sum('edited_line_count');

            return $retailer;
        });
    }

    private function prePurchaseIssuesForOrder(int $orderId): Collection
    {
        return DB::table('purchase_issues as pi')
            ->leftJoin('order_items as oi', 'oi.id', '=', 'pi.order_item_id')
            ->leftJoin('retailers as r', 'r.id', '=', 'pi.retailer_id')
            ->leftJoin('users as u', 'u.id', '=', 'pi.created_by_user_id')
            ->select([
                'pi.*',
                'oi.item_name',
                'oi.product_code',
                DB::raw('COALESCE(r.name, "Unknown retailer") as retailer_name'),
                'oi.item_name',
                'oi.product_code',
                'oi.product_url',
                'u.name as created_by_name',
            ])
            ->where('pi.order_id', $orderId)
            ->where('pi.issue_stage', 'pre_purchase')
            ->whereIn('pi.status', ['open', 'awaiting_customer'])
            ->orderByRaw("FIELD(pi.status, 'awaiting_customer', 'open')")
            ->orderByDesc('pi.created_at')
            ->get()
            ->map(function ($issue) {
                $issue->issue_label = $this->prePurchaseIssueTypeLabel((string) $issue->issue_type);
                return $issue;
            });
    }

    private function orderItemTotalsSubquery(array $orderIds = [], array $lineageOrderIds = [])
    {
        $eventOrderIds = ! empty($lineageOrderIds) ? $lineageOrderIds : $orderIds;

        $purchaseTotals = $this->purchaseTotalsSubqueryForOrderIds($eventOrderIds);
        $arrivalTotals = $this->arrivalTotalsSubqueryForOrderIds($eventOrderIds);
        $issueTotals = $this->issueTotalsSubqueryForOrderIds($eventOrderIds);

        $query = DB::table('order_items as oi')
            ->leftJoinSub($purchaseTotals, 'pt', function ($join) {
                $join->on('pt.root_item_id', '=', DB::raw('COALESCE(oi.root_item_id, oi.id)'));
            })
            ->leftJoinSub($arrivalTotals, 'at', function ($join) {
                $join->on('at.root_item_id', '=', DB::raw('COALESCE(oi.root_item_id, oi.id)'));
            })
            ->leftJoinSub($issueTotals, 'pit', function ($join) {
                $join->on('pit.root_item_id', '=', DB::raw('COALESCE(oi.root_item_id, oi.id)'));
            })
            ->selectRaw('oi.order_id')
            ->selectRaw('SUM(' . $this->itemLifecycle()->activeItemQtyExpression('oi') . ') as active_item_qty')
            ->selectRaw('SUM(GREATEST(0, COALESCE(pt.gross_purchased_qty, 0) - COALESCE(pit.return_to_buy_issue_qty, 0))) as purchased_qty')
            ->selectRaw('SUM(COALESCE(at.arrived_qty, 0)) as arrived_qty')
            ->selectRaw('SUM(GREATEST(0, GREATEST(0, COALESCE(pt.gross_purchased_qty, 0) - COALESCE(pit.return_to_buy_issue_qty, 0)) - COALESCE(pt.terminal_problem_qty, 0) - COALESCE(at.arrived_qty, 0))) as awaiting_arrival_qty')
            ->selectRaw('SUM(COALESCE(pit.active_pre_purchase_issue_qty, 0)) as pre_purchase_problem_qty')
            ->selectRaw('SUM(GREATEST(0, (' . $this->itemLifecycle()->activeItemQtyExpression('oi') . ') - GREATEST(0, COALESCE(pt.gross_purchased_qty, 0) - COALESCE(pit.return_to_buy_issue_qty, 0)) - COALESCE(pt.terminal_problem_qty, 0) - COALESCE(pit.active_issue_qty, 0) - COALESCE(pit.resolved_terminal_issue_qty, 0))) as remaining_to_buy_qty');

        $this->itemLifecycle()->applyPurchasableItemConstraints($query, 'oi');

        if (! empty($orderIds)) {
            $query->whereIn('oi.order_id', $orderIds);
        }

        return $query->groupBy('oi.order_id');
    }

    private function purchaseTotalsSubqueryForOrderIds(array $orderIds = [])
    {
        $query = DB::table('order_item_purchases as oip')
            ->selectRaw('oip.root_item_id')
            ->selectRaw("SUM(CASE WHEN oip.status IN ('purchased','ordered','received') AND oip.cancelled_at IS NULL THEN oip.qty ELSE 0 END) as gross_purchased_qty")
            ->selectRaw("SUM(CASE WHEN oip.status IN ('unfulfilled','failed','problem','supplier_problem','supplier_cancelled','cancelled','refunded','retailer_refunded','lost','damaged','wrong_item','unavailable') AND oip.cancelled_at IS NULL AND COALESCE(oip.resolution_status, 'pending') = 'pending' AND COALESCE(oip.resolution_action, '') <> 'return_to_buy' THEN oip.qty ELSE 0 END) as terminal_problem_qty")
            ->selectRaw("SUM(CASE WHEN oip.status IN ('unfulfilled','failed','problem','supplier_problem','supplier_cancelled','cancelled','refunded','retailer_refunded','lost','damaged','wrong_item','unavailable') AND oip.cancelled_at IS NULL AND COALESCE(oip.resolution_status, 'pending') = 'pending' AND COALESCE(oip.resolution_action, '') <> 'return_to_buy' THEN oip.qty ELSE 0 END) as pending_problem_qty")
            ->selectRaw('MAX(oip.created_at) as latest_purchase_event_at')
            ->selectRaw('COUNT(*) as purchase_event_count');

        if (! empty($orderIds)) {
            $query->whereIn('oip.order_id', $orderIds);
        }

        return $query->groupBy('oip.root_item_id');
    }

    private function arrivalTotalsSubqueryForOrderIds(array $orderIds = [])
    {
        $query = DB::table('purchase_arrival_assignments as paa')
            ->selectRaw('paa.root_item_id')
            ->selectRaw('SUM(CASE WHEN paa.undone_at IS NULL THEN paa.qty ELSE 0 END) as arrived_qty')
            ->selectRaw('MAX(paa.matched_at) as latest_arrival_at');

        if (! empty($orderIds)) {
            $query->whereIn('paa.order_id', $orderIds);
        }

        return $query->groupBy('paa.root_item_id');
    }

    private function issueTotalsSubqueryForOrderIds(array $orderIds = [])
    {
        $query = DB::table('purchase_issues as pi')
            ->selectRaw('pi.root_item_id')
            ->selectRaw("SUM(CASE WHEN pi.status IN ('open','awaiting_customer') AND COALESCE(pi.resolution_type, '') <> 'return_to_buy' THEN COALESCE(pi.affected_qty, pi.qty, 1) ELSE 0 END) as active_issue_qty")
            ->selectRaw("SUM(CASE WHEN pi.status IN ('open','awaiting_customer') AND COALESCE(pi.issue_stage, 'pre_purchase') = 'pre_purchase' AND COALESCE(pi.resolution_type, '') <> 'return_to_buy' THEN COALESCE(pi.affected_qty, pi.qty, 1) ELSE 0 END) as active_pre_purchase_issue_qty")
            ->selectRaw("SUM(CASE WHEN pi.status IN ('open','awaiting_customer') AND COALESCE(pi.issue_stage, 'pre_purchase') IN ('post_purchase','arrival') AND COALESCE(pi.resolution_type, '') <> 'return_to_buy' THEN COALESCE(pi.affected_qty, pi.qty, 1) ELSE 0 END) as active_post_purchase_issue_qty")
            ->selectRaw("SUM(CASE WHEN pi.status IN ('returned_to_buy','resolved') AND COALESCE(pi.resolution_type, '') = 'return_to_buy' THEN COALESCE(pi.affected_qty, pi.qty, 1) ELSE 0 END) as return_to_buy_issue_qty")
            ->selectRaw("SUM(CASE WHEN pi.status = 'resolved' AND pi.resolution_type IN ('customer_cancelled','customer_refunded','duplicate_item','no_longer_required') THEN COALESCE(pi.affected_qty, pi.qty, 1) ELSE 0 END) as resolved_terminal_issue_qty")
            ->selectRaw("SUM(CASE WHEN pi.status = 'awaiting_customer' AND COALESCE(pi.resolution_type, '') <> 'return_to_buy' THEN 1 ELSE 0 END) as awaiting_customer_issue_count")
            ->selectRaw('COUNT(*) as issue_history_count')
            ->selectRaw('MAX(pi.created_at) as latest_issue_at');

        if (! empty($orderIds)) {
            $query->whereIn('pi.order_id', $orderIds);
        }

        return $query->groupBy('pi.root_item_id');
    }


    private function ancestorOrderIdsForOrderId(int $orderId): array
    {
        $order = DB::table('orders')
            ->select(['id', 'parent_order_id'])
            ->where('id', $orderId)
            ->first();

        if (! $order) {
            return [$orderId];
        }

        return $this->ancestorOrderIdsForOrderIds(collect([$order]));
    }

    private function ancestorOrderIdsForOrderIds(Collection $orders): array
    {
        $ids = $orders->pluck('id')->filter()->map(fn ($id) => (int) $id)->values();
        $parentIds = $orders->pluck('parent_order_id')->filter()->map(fn ($id) => (int) $id)->values();

        $seen = $ids->merge($parentIds)->unique()->values();
        $frontier = $parentIds->unique()->values();

        // Walk historical parent chains so purchase/arrival events recorded against an older
        // superseded order still count against the current replacement order's root_item_id.
        // This is intentionally read-only; it repairs the queue calculation, not the database.
        while ($frontier->isNotEmpty()) {
            $parents = DB::table('orders')
                ->select(['id', 'parent_order_id'])
                ->whereIn('id', $frontier->all())
                ->get();

            $next = collect();

            foreach ($parents as $parent) {
                $parentId = (int) $parent->id;
                if (! $seen->contains($parentId)) {
                    $seen->push($parentId);
                }

                if (! empty($parent->parent_order_id)) {
                    $grandParentId = (int) $parent->parent_order_id;
                    if (! $seen->contains($grandParentId)) {
                        $next->push($grandParentId);
                    }
                }
            }

            $frontier = $next->unique()->values();
        }

        return $seen->unique()->values()->all();
    }


    private function writePurchaseDeskEvent(string $entityType, int $entityId, string $eventType, array $payload, ?int $userId = null): void
    {
        DB::table('order_events')->insert([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'event_type' => $eventType,
            'payload' => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'created_by_user_id' => $userId,
            'created_at' => now(),
        ]);
    }

    private function writeActivityLog(string $subjectType, int $subjectId, string $title, string $body, ?int $userId = null): void
    {
        DB::table('activity_logs')->insert([
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'type' => 'purchasing',
            'title' => $title,
            'body' => $body,
            'occurred_at' => now(),
            'created_by_user_id' => $userId,
            'updated_by_user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }



    private function activeAttentionFlagsForOrderIds(array $orderIds = []): Collection
    {
        if (! Schema::hasTable('purchase_attention_flags')) {
            return collect();
        }

        $query = DB::table('purchase_attention_flags as paf')
            ->leftJoin('users as u', 'u.id', '=', 'paf.created_by_user_id')
            ->select([
                'paf.*',
                'u.name as created_by_name',
            ])
            ->whereNull('paf.cleared_at')
            ->orderByDesc('paf.created_at')
            ->orderByDesc('paf.id');

        if (! empty($orderIds)) {
            $query->whereIn('paf.order_id', $orderIds);
        }

        return $query->get()->map(function ($flag) {
            $flag->attention_label = $this->attentionTypeLabel((string) $flag->attention_type);
            return $flag;
        });
    }

    private function normaliseAttentionType(string $type): string
    {
        $type = trim($type);
        $allowed = array_keys($this->attentionTypeOptions());

        return in_array($type, $allowed, true) ? $type : 'other';
    }


    private function prePurchaseIssueTypeOptions(): array
    {
        return [
            'out_of_stock' => 'Out of stock',
            'awaiting_customer_approval' => 'Awaiting customer approval',
            'price_increased' => 'Price increased',
            'supplier_unavailable' => 'Supplier unavailable',
            'website_unavailable' => 'Website unavailable',
            'other' => 'Other',
        ];
    }

    private function normalisePrePurchaseIssueType(string $type): string
    {
        $type = trim($type);
        return array_key_exists($type, $this->prePurchaseIssueTypeOptions()) ? $type : 'other';
    }

    private function prePurchaseIssueTypeLabel(string $type): string
    {
        return $this->prePurchaseIssueTypeOptions()[$type] ?? 'Other';
    }

    private function attentionTypeOptions(): array
    {
        return [
            'documentation' => 'Check documentation',
            'accessories' => 'Check accessories',
            'damage_inspection' => 'Inspect package condition',
            'colour' => 'Verify colour',
            'serial_number' => 'Verify serial number',
            'photograph' => 'Photograph contents',
            'other' => 'Other purple note',
        ];
    }

    private function attentionTypeLabel(string $type): string
    {
        return $this->attentionTypeOptions()[$type] ?? 'Other purple note';
    }

    private function activeSuppliers(): Collection
    {
        return DB::table('retailers')
            ->select(['id', 'name', 'base_url'])
            ->where('is_active', 1)
            ->orderBy('name')
            ->get();
    }

    private function settlementTotalsSubquery()
    {
        return DB::table('order_transactions')
            ->selectRaw('order_id')
            ->selectRaw("SUM(CASE
                WHEN status = 'recorded' AND type IN ('payment', 'credit_application') THEN amount
                WHEN status = 'recorded' AND type IN ('payment_void', 'credit_application_void', 'refund') THEN -ABS(amount)
                WHEN status = 'recorded' AND type = 'refund_void' THEN ABS(amount)
                ELSE 0
            END) as settled_amount")
            ->groupBy('order_id');
    }
}
