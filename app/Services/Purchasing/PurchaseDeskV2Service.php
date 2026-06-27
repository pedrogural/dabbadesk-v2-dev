<?php

namespace App\Services\Purchasing;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PurchaseDeskV2Service
{
    public function index(array $filters = []): array
    {
        $q = trim((string) ($filters['q'] ?? ''));
        $payment = (string) ($filters['payment'] ?? 'paid_or_part');

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
            ->whereNotIn('o.status', ['cancelled', 'refunded', 'superseded'])
            ->whereNull('o.cancelled_at')
            ->whereNotExists(function ($child) {
                $child->from('orders as newer')
                    ->whereColumn('newer.parent_order_id', 'o.id')
                    ->whereNotIn('newer.status', ['cancelled', 'refunded', 'superseded']);
            });

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
                'filters' => ['q' => $q, 'payment' => $payment],
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

        $allItems = $this->itemRowsForOrder($orderId, '', 'all');
        $items = $this->itemRowsForOrder($orderId, $q, $view);
        $issues = $this->prePurchaseIssuesForOrder($orderId);
        $lineageOrderIds = $this->ancestorOrderIdsForOrderId($orderId);
        $purchaseEvents = $this->purchaseEventsForOrderIds($lineageOrderIds);
        $purchaseEventsByRoot = $purchaseEvents->groupBy(fn ($event) => (int) $event->root_item_id);
        $issuesByRoot = $issues->groupBy(fn ($issue) => (int) $issue->root_item_id);

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
                    'actionable_count' => (int) $rows->filter(fn ($item) => (int) $item->remaining_to_buy_qty > 0 || (int) $item->active_pre_purchase_issue_qty > 0)->count(),
                ];
            })
            ->sortBy('retailer_name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

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
            'filters' => [
                'q' => $q,
                'view' => $view,
            ],
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
                'oi.status as item_status',
                'oi.requires_inspection',
                'oi.inspection_note',
                'ore.retailer_id',
                DB::raw('COALESCE(r.name, ore.retailer_name, "Unknown retailer") as retailer_name'),
                DB::raw('COALESCE(oi.root_item_id, oi.id) as lineage_root_id'),
                DB::raw('CASE WHEN oi.status IN ("cancelled", "refunded", "deleted") THEN 0 ELSE oi.quantity END as active_item_qty'),
                DB::raw('COALESCE(pt.gross_purchased_qty, 0) as gross_purchased_qty'),
                DB::raw('GREATEST(0, COALESCE(pt.gross_purchased_qty, 0) - COALESCE(pit.return_to_buy_issue_qty, 0)) as purchased_qty'),
                DB::raw('COALESCE(pt.terminal_problem_qty, 0) as terminal_problem_qty'),
                DB::raw('COALESCE(pit.active_issue_qty, 0) as active_issue_qty'),
                DB::raw('COALESCE(pit.active_pre_purchase_issue_qty, 0) as active_pre_purchase_issue_qty'),
                DB::raw('COALESCE(pit.resolved_terminal_issue_qty, 0) as resolved_terminal_issue_qty'),
                DB::raw('COALESCE(at.arrived_qty, 0) as arrived_qty'),
                DB::raw('GREATEST(0, (CASE WHEN oi.status IN ("cancelled", "refunded", "deleted") THEN 0 ELSE oi.quantity END) - GREATEST(0, COALESCE(pt.gross_purchased_qty, 0) - COALESCE(pit.return_to_buy_issue_qty, 0)) - COALESCE(pt.terminal_problem_qty, 0) - COALESCE(pit.active_issue_qty, 0) - COALESCE(pit.resolved_terminal_issue_qty, 0)) as remaining_to_buy_qty'),
                DB::raw('GREATEST(0, GREATEST(0, COALESCE(pt.gross_purchased_qty, 0) - COALESCE(pit.return_to_buy_issue_qty, 0)) - COALESCE(pt.terminal_problem_qty, 0) - COALESCE(at.arrived_qty, 0)) as awaiting_arrival_qty'),
            ])
            ->where('oi.order_id', $orderId)
            ->whereNotIn('oi.status', ['cancelled', 'refunded', 'deleted']);

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
        $query = DB::table('order_item_purchases as oip')
            ->leftJoin('retailers as r', 'r.id', '=', 'oip.retailer_id')
            ->leftJoin('users as u', 'u.id', '=', 'oip.created_by_user_id')
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
                DB::raw('COALESCE(r.name, "Unknown retailer") as retailer_name'),
                'u.name as created_by_name',
            ]);

        if (! empty($orderIds)) {
            $query->whereIn('oip.order_id', $orderIds);
        }

        return $query
            ->whereNull('oip.cancelled_at')
            ->orderByDesc(DB::raw('COALESCE(oip.ordered_at, oip.created_at)'))
            ->orderByDesc('oip.id')
            ->get();
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
                'u.name as created_by_name',
            ])
            ->where('pi.order_id', $orderId)
            ->where('pi.issue_stage', 'pre_purchase')
            ->whereIn('pi.status', ['open', 'awaiting_customer'])
            ->orderByRaw("FIELD(pi.status, 'awaiting_customer', 'open')")
            ->orderByDesc('pi.created_at')
            ->get();
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
            ->selectRaw('SUM(CASE WHEN oi.status IN ("cancelled", "refunded", "deleted") THEN 0 ELSE oi.quantity END) as active_item_qty')
            ->selectRaw('SUM(GREATEST(0, COALESCE(pt.gross_purchased_qty, 0) - COALESCE(pit.return_to_buy_issue_qty, 0))) as purchased_qty')
            ->selectRaw('SUM(COALESCE(at.arrived_qty, 0)) as arrived_qty')
            ->selectRaw('SUM(GREATEST(0, GREATEST(0, COALESCE(pt.gross_purchased_qty, 0) - COALESCE(pit.return_to_buy_issue_qty, 0)) - COALESCE(pt.terminal_problem_qty, 0) - COALESCE(at.arrived_qty, 0))) as awaiting_arrival_qty')
            ->selectRaw('SUM(COALESCE(pit.active_pre_purchase_issue_qty, 0)) as pre_purchase_problem_qty')
            ->selectRaw('SUM(GREATEST(0, (CASE WHEN oi.status IN ("cancelled", "refunded", "deleted") THEN 0 ELSE oi.quantity END) - GREATEST(0, COALESCE(pt.gross_purchased_qty, 0) - COALESCE(pit.return_to_buy_issue_qty, 0)) - COALESCE(pt.terminal_problem_qty, 0) - COALESCE(pit.active_issue_qty, 0) - COALESCE(pit.resolved_terminal_issue_qty, 0))) as remaining_to_buy_qty')
            ->whereNotIn('oi.status', ['cancelled', 'refunded', 'deleted']);

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
