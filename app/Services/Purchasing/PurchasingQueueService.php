<?php

namespace App\Services\Purchasing;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PurchasingQueueService
{
    /**
     * Build the order-first Purchasing Desk queue.
     *
     * The important business rule is that purchasing is always grouped by
     * customer order. We never merge items from different customer orders just
     * because they share the same retailer.
     */
    public function queue(array $filters = []): array
    {
        $tab = $this->normaliseTab($filters['tab'] ?? 'to_buy');
        $payment = $this->normalisePayment($filters['payment'] ?? 'paid_or_part');
        $search = trim((string) ($filters['q'] ?? ''));
        $mineOnly = (bool) ($filters['mine'] ?? false);
        $userId = isset($filters['user_id']) ? (int) $filters['user_id'] : null;

        $items = $this->itemRows($search, null, $mineOnly ? $userId : null);
        $orders = $this->groupOrders($items);

        // Counts should match the selected payment filter.
        // Example: Paid-only + To Buy should not show a global unpaid count.
        $paymentScopedOrders = $orders
            ->filter(fn ($order) => $this->matchesPayment($order['payment_status'], $payment))
            ->values();

        // Build tab counts from the exact same bucket rules used to display rows.
        // This prevents the header badges saying "7" while the selected queue is empty.
        $summary = [
            'to_buy' => $paymentScopedOrders->filter(fn ($order) => $this->isToBuyOrder($order))->count(),
            'awaiting_arrival' => $paymentScopedOrders->filter(fn ($order) => $this->isAwaitingArrivalOrder($order))->count(),
            'problems' => $paymentScopedOrders->filter(fn ($order) => $this->isProblemOrder($order))->count(),
            'completed' => $paymentScopedOrders->filter(fn ($order) => $this->isCompletedOrder($order))->count(),
            'total' => $paymentScopedOrders->count(),
            'purchases' => $this->purchaseRows($search, $mineOnly ? $userId : null)->count(),
        ];

        $filtered = $paymentScopedOrders
            ->filter(fn ($order) => $this->matchesTab($order, $tab))
            ->values();

        return [
            'filters' => [
                'tab' => $tab,
                'payment' => $payment,
                'q' => $search,
                'mine' => $mineOnly,
            ],
            'summary' => $summary,
            'orders' => $filtered,
            'purchaseRows' => $tab === 'purchases' ? $this->purchaseRows($search, $mineOnly ? $userId : null) : collect(),
            'tabs' => $this->tabs($summary),
            'paymentOptions' => $this->paymentOptions(),
        ];
    }

    public function workspace(int $orderId): ?array
    {
        $order = DB::table('orders')
            ->leftJoin('countries', 'countries.id', '=', 'orders.bill_to_country_id')
            ->select([
                'orders.*',
                'countries.name as bill_to_country_name',
            ])
            ->where('orders.id', $orderId)
            ->first();

        if (! $order) {
            return null;
        }

        $items = $this->itemRows('', $orderId);
        $grouped = $this->groupOrders($items);
        $queueOrder = $grouped->first();

        $retailers = $items
            ->groupBy(fn ($item) => (string) ($item->retailer_id ?? 0) . '|' . ($item->retailer_name ?: 'Unknown retailer'))
            ->map(function (Collection $rows) {
                $first = $rows->first();

                return [
                    'retailer_id' => $first->retailer_id,
                    'retailer_name' => $first->retailer_name ?: 'Unknown retailer',
                    'items' => $rows->values(),
                    'remaining_to_buy_qty' => (int) $rows->sum('remaining_to_buy_qty'),
                    'awaiting_arrival_qty' => (int) $rows->sum('awaiting_arrival_qty'),
                    'problem_qty' => (int) $rows->sum('problem_qty'),
                    'purchased_qty' => (int) $rows->sum('purchased_qty'),
                    'arrived_qty' => (int) $rows->sum('arrived_qty'),
                ];
            })
            ->values();

        $purchases = DB::table('order_item_purchases as oip')
            ->leftJoin('order_items as oi', 'oi.id', '=', 'oip.order_item_id')
            ->leftJoin('retailers as r', 'r.id', '=', 'oip.retailer_id')
            ->select([
                'oip.*',
                'oi.item_name',
                'oi.product_code',
                'oi.requires_inspection',
                'oi.inspection_note',
                'r.name as master_retailer_name',
            ])
            ->whereIn('oip.root_item_id', $items->pluck('lineage_root_id')->filter()->unique()->values())
            ->orderByDesc('oip.created_at')
            ->limit(100)
            ->get();

        $arrivals = DB::table('purchase_arrival_assignments as paa')
            ->leftJoin('order_items as oi', 'oi.id', '=', 'paa.order_item_id')
            ->select([
                'paa.*',
                'oi.item_name',
                'oi.product_code',
            ])
            ->whereIn('paa.root_item_id', $items->pluck('lineage_root_id')->filter()->unique()->values())
            ->whereNull('paa.undone_at')
            ->orderByDesc('paa.matched_at')
            ->limit(100)
            ->get();

        return [
            'order' => $order,
            'queueOrder' => $queueOrder,
            'items' => $items,
            'retailers' => $retailers,
            'purchases' => $purchases,
            'arrivals' => $arrivals,
            'purchasesByRoot' => $purchases->groupBy('root_item_id'),
            'tabs' => [
                'overview' => 'Overview',
                'buy' => 'Buy',
                'awaiting' => 'Awaiting Arrival',
                'problems' => 'Problems',
                'timeline' => 'Timeline',
            ],
        ];
    }

    private function itemRows(string $search = '', ?int $orderId = null, ?int $mineUserId = null): Collection
    {
        $purchaseTotals = DB::table('order_item_purchases')
            ->selectRaw('root_item_id')
            ->selectRaw("SUM(CASE WHEN status IN ('purchased','ordered','received') AND cancelled_at IS NULL THEN qty ELSE 0 END) as purchased_qty")
            ->selectRaw("SUM(CASE WHEN status IN ('unfulfilled','failed','problem','supplier_problem','supplier_cancelled','cancelled','refunded','retailer_refunded','lost','damaged','wrong_item','unavailable') THEN qty ELSE 0 END) as terminal_problem_qty")
            ->selectRaw("SUM(CASE WHEN status IN ('unfulfilled','failed','problem','supplier_problem','supplier_cancelled','cancelled','refunded','retailer_refunded','lost','damaged','wrong_item','unavailable') AND COALESCE(resolution_status, 'pending') = 'pending' THEN qty ELSE 0 END) as pending_problem_qty")
            ->selectRaw('MAX(created_at) as latest_purchase_event_at')
            ->selectRaw('COUNT(*) as purchase_event_count')
            ->groupBy('root_item_id');

        $arrivalTotals = DB::table('purchase_arrival_assignments')
            ->selectRaw('root_item_id')
            ->selectRaw('SUM(CASE WHEN undone_at IS NULL THEN qty ELSE 0 END) as arrived_qty')
            ->selectRaw('MAX(matched_at) as latest_arrival_at')
            ->groupBy('root_item_id');

        $settlementTotals = DB::table('order_transactions')
            ->selectRaw('order_id')
            ->selectRaw("SUM(CASE
                WHEN status = 'recorded' AND type IN ('payment', 'credit_application') THEN amount
                WHEN status = 'recorded' AND type IN ('payment_void', 'credit_application_void', 'refund') THEN -ABS(amount)
                WHEN status = 'recorded' AND type = 'refund_void' THEN ABS(amount)
                ELSE 0
            END) as settled_amount")
            ->groupBy('order_id');

        $query = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->leftJoin('order_retailers as ore', 'ore.id', '=', 'oi.order_retailer_id')
            ->leftJoin('retailers as r', 'r.id', '=', 'ore.retailer_id')
            ->leftJoinSub($purchaseTotals, 'pt', function ($join) {
                $join->on('pt.root_item_id', '=', DB::raw('COALESCE(oi.root_item_id, oi.id)'));
            })
            ->leftJoinSub($arrivalTotals, 'at', function ($join) {
                $join->on('at.root_item_id', '=', DB::raw('COALESCE(oi.root_item_id, oi.id)'));
            })
            ->leftJoinSub($settlementTotals, 'st', function ($join) {
                $join->on('st.order_id', '=', 'o.id');
            })
            ->select([
                'o.id as order_id',
                'o.order_number',
                'o.status as order_status',
                'o.purchase_mode',
                'o.bill_to_name',
                'o.bill_to_company',
                'o.bill_to_email',
                'o.grand_total',
                'o.created_at as order_created_at',
                'oi.id as item_id',
                'oi.order_retailer_id',
                'oi.item_name',
                'oi.product_code',
                'oi.product_url',
                'oi.marketplace_seller',
                'oi.quantity',
                'oi.unit_price',
                'oi.line_total',
                'oi.status as item_status',
                'oi.purchase_problem_reason',
                'oi.purchase_problem_note',
                'oi.requires_inspection',
                'oi.inspection_note',
                'ore.retailer_id',
                DB::raw('COALESCE(r.name, ore.retailer_name, "Unknown retailer") as retailer_name'),
                DB::raw('COALESCE(oi.root_item_id, oi.id) as lineage_root_id'),
                DB::raw('COALESCE(pt.purchased_qty, 0) as purchased_qty'),
                DB::raw('COALESCE(pt.terminal_problem_qty, 0) as terminal_problem_qty'),
                DB::raw("CASE WHEN oi.purchase_problem_reason IS NOT NULL AND oi.purchase_problem_reason <> '' THEN oi.quantity ELSE 0 END as item_sourcing_problem_qty"),
                DB::raw("COALESCE(pt.pending_problem_qty, 0) + CASE WHEN oi.purchase_problem_reason IS NOT NULL AND oi.purchase_problem_reason <> '' THEN oi.quantity ELSE 0 END as problem_qty"),
                DB::raw('COALESCE(at.arrived_qty, 0) as arrived_qty'),
                DB::raw('COALESCE(pt.latest_purchase_event_at, NULL) as latest_purchase_event_at'),
                DB::raw('COALESCE(at.latest_arrival_at, NULL) as latest_arrival_at'),
                DB::raw('COALESCE(pt.purchase_event_count, 0) as purchase_event_count'),
                DB::raw('COALESCE(st.settled_amount, 0) as settled_amount'),
                DB::raw("CASE
                    WHEN COALESCE(st.settled_amount, 0) >= o.grand_total AND o.grand_total > 0 THEN 'paid'
                    WHEN COALESCE(st.settled_amount, 0) > 0 THEN 'part_paid'
                    ELSE 'unpaid'
                END as payment_status"),
                DB::raw("GREATEST(0, oi.quantity - COALESCE(pt.purchased_qty, 0) - COALESCE(pt.terminal_problem_qty, 0) - CASE WHEN oi.purchase_problem_reason IS NOT NULL AND oi.purchase_problem_reason <> '' THEN oi.quantity ELSE 0 END) as remaining_to_buy_qty"),
                DB::raw('GREATEST(0, COALESCE(pt.purchased_qty, 0) - COALESCE(pt.terminal_problem_qty, 0) - COALESCE(at.arrived_qty, 0)) as awaiting_arrival_qty'),
            ])
            ->whereNull('o.cancelled_at')
            ->whereNull('o.completed_at')
            ->where('o.purchase_mode', '<>', 'customer_self_purchase')
            ->where(function ($query) {
                $query->whereNull('o.status')
                    ->orWhereNotIn('o.status', ['cancelled', 'completed', 'superseded']);
            })
            ->where(function ($query) {
                $query->whereNull('oi.status')
                    ->orWhereNotIn('oi.status', ['cancelled', 'refunded', 'returned', 'collected', 'delivered']);
            })
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('orders as child_orders')
                    ->whereColumn('child_orders.parent_order_id', 'o.id')
                    ->whereNull('child_orders.cancelled_at')
                    ->where(function ($child) {
                        $child->whereNull('child_orders.status')
                            ->orWhereNotIn('child_orders.status', ['cancelled', 'superseded']);
                    });
            });

        if ($orderId !== null) {
            $query->where('o.id', $orderId);
        }

        if ($mineUserId !== null && $mineUserId > 0) {
            $query->where(function ($query) use ($mineUserId) {
                $query->where('o.created_by_user_id', $mineUserId)
                    ->orWhere('o.updated_by_user_id', $mineUserId);
            });
        }

        if ($search !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $search) . '%';
            $query->where(function ($query) use ($like) {
                $query->where('o.order_number', 'like', $like)
                    ->orWhere('o.bill_to_name', 'like', $like)
                    ->orWhereRaw("CONCAT(COALESCE(o.bill_to_name, ''), ' ', COALESCE(o.bill_to_email, '')) LIKE ?", [$like])
                    ->orWhere('o.bill_to_company', 'like', $like)
                    ->orWhere('o.bill_to_email', 'like', $like)
                    ->orWhere('oi.item_name', 'like', $like)
                    ->orWhere('oi.product_code', 'like', $like)
                    ->orWhere('ore.retailer_name', 'like', $like)
                    ->orWhere('r.name', 'like', $like)
                    ->orWhereExists(function ($exists) use ($like) {
                        $exists->selectRaw('1')
                            ->from('order_item_purchases as search_oip')
                            ->whereColumn('search_oip.root_item_id', DB::raw('COALESCE(oi.root_item_id, oi.id)'))
                            ->where(function ($purchaseSearch) use ($like) {
                                $purchaseSearch->where('search_oip.retailer_order_reference', 'like', $like)
                                    ->orWhere('search_oip.marketplace_seller', 'like', $like)
                                    ->orWhere('search_oip.problem_code', 'like', $like)
                                    ->orWhere('search_oip.problem_notes', 'like', $like)
                                    ->orWhere('search_oip.note', 'like', $like);
                            });
                    });
            });
        }

        return $query
            ->orderByRaw('CAST(o.order_number AS UNSIGNED) DESC')
            ->orderBy('ore.retailer_name')
            ->orderBy('oi.sort_order')
            ->limit(5000)
            ->get()
            ->map(function ($row) {
                $row->quantity = (int) $row->quantity;
                $row->purchased_qty = (int) $row->purchased_qty;
                $row->terminal_problem_qty = (int) $row->terminal_problem_qty;
                $row->item_sourcing_problem_qty = (int) ($row->item_sourcing_problem_qty ?? 0);
                $row->problem_qty = (int) $row->problem_qty;
                $row->arrived_qty = (int) $row->arrived_qty;
                $row->remaining_to_buy_qty = (int) $row->remaining_to_buy_qty;
                $row->awaiting_arrival_qty = (int) $row->awaiting_arrival_qty;
                $row->purchase_event_count = (int) $row->purchase_event_count;
                $row->requires_inspection = (int) ($row->requires_inspection ?? 0);
                $row->inspection_note = trim((string) ($row->inspection_note ?? ''));

                return $row;
            });
    }


    private function purchaseRows(string $search = '', ?int $mineUserId = null): Collection
    {
        $arrivalTotals = DB::table('purchase_arrival_assignments')
            ->selectRaw('order_item_purchase_id')
            ->selectRaw('SUM(CASE WHEN undone_at IS NULL THEN qty ELSE 0 END) as active_arrival_qty')
            ->groupBy('order_item_purchase_id');

        $query = DB::table('order_item_purchases as oip')
            ->join('orders as o', 'o.id', '=', 'oip.order_id')
            ->leftJoin('order_items as oi', 'oi.id', '=', 'oip.order_item_id')
            ->leftJoin('retailers as r', 'r.id', '=', 'oip.retailer_id')
            ->leftJoin('users as u', 'u.id', '=', 'oip.created_by_user_id')
            ->leftJoinSub($arrivalTotals, 'arr', function ($join) {
                $join->on('arr.order_item_purchase_id', '=', 'oip.id');
            })
            ->select([
                'oip.*',
                'o.order_number',
                'o.bill_to_name',
                'o.bill_to_company',
                'o.bill_to_email',
                'oi.item_name',
                'oi.product_code',
                'oi.product_url',
                DB::raw('COALESCE(r.name, "Unknown retailer") as retailer_name'),
                'u.name as recorded_by_name',
                DB::raw('COALESCE(arr.active_arrival_qty, 0) as active_arrival_qty'),
            ])
            ->whereNull('o.cancelled_at')
            ->whereNull('o.completed_at')
            ->whereIn('oip.status', ['purchased', 'ordered', 'received']);

        if ($mineUserId !== null && $mineUserId > 0) {
            $query->where(function ($query) use ($mineUserId) {
                $query->where('oip.created_by_user_id', $mineUserId)
                    ->orWhere('oip.updated_by_user_id', $mineUserId)
                    ->orWhere('o.created_by_user_id', $mineUserId)
                    ->orWhere('o.updated_by_user_id', $mineUserId);
            });
        }

        if ($search !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $search) . '%';
            $query->where(function ($query) use ($like) {
                $query->where('o.order_number', 'like', $like)
                    ->orWhere('o.bill_to_name', 'like', $like)
                    ->orWhere('o.bill_to_company', 'like', $like)
                    ->orWhere('o.bill_to_email', 'like', $like)
                    ->orWhereRaw("CONCAT(COALESCE(o.bill_to_name, ''), ' ', COALESCE(o.bill_to_email, '')) LIKE ?", [$like])
                    ->orWhere('oi.item_name', 'like', $like)
                    ->orWhere('oi.product_code', 'like', $like)
                    ->orWhere('r.name', 'like', $like)
                    ->orWhere('oip.retailer_order_reference', 'like', $like)
                    ->orWhere('oip.marketplace_seller', 'like', $like)
                    ->orWhere('oip.problem_code', 'like', $like)
                    ->orWhere('oip.problem_notes', 'like', $like)
                    ->orWhere('oip.note', 'like', $like);
            });
        }

        return $query
            ->orderByDesc('oip.created_at')
            ->limit(750)
            ->get()
            ->map(function ($row) {
                $row->qty = (int) $row->qty;
                $row->active_arrival_qty = (int) ($row->active_arrival_qty ?? 0);
                $row->can_edit = empty($row->cancelled_at) && $row->active_arrival_qty === 0;
                return $row;
            });
    }

    private function groupOrders(Collection $items): Collection
    {
        return $items
            ->groupBy('order_id')
            ->map(function (Collection $rows) {
                $first = $rows->first();
                $remaining = (int) $rows->sum('remaining_to_buy_qty');
                $awaiting = (int) $rows->sum('awaiting_arrival_qty');
                $problem = (int) $rows->sum('problem_qty');
                $purchased = (int) $rows->sum('purchased_qty');
                $arrived = (int) $rows->sum('arrived_qty');
                $requested = (int) $rows->sum('quantity');
                $retailerCount = $rows->pluck('retailer_name')->filter()->unique()->count();
                $inspectionCount = $rows->filter(fn ($item) => (int) ($item->requires_inspection ?? 0) === 1)->count();

                $action = 'Completed';
                if ($problem > 0) {
                    $action = 'Resolve Problem';
                } elseif ($remaining > 0) {
                    $action = 'Buy Items';
                } elseif ($awaiting > 0) {
                    $action = 'Await Arrival';
                }

                return [
                    'order_id' => (int) $first->order_id,
                    'order_number' => $first->order_number,
                    'customer' => $this->customerLabel($first),
                    'email' => $first->bill_to_email,
                    'order_status' => $first->order_status,
                    'payment_status' => $first->payment_status,
                    'grand_total' => (float) $first->grand_total,
                    'settled_amount' => (float) $first->settled_amount,
                    'retailer_count' => $retailerCount,
                    'item_count' => $rows->count(),
                    'requested_qty' => $requested,
                    'purchased_qty' => $purchased,
                    'arrived_qty' => $arrived,
                    'remaining_to_buy_qty' => $remaining,
                    'awaiting_arrival_qty' => $awaiting,
                    'problem_qty' => $problem,
                    'inspection_count' => $inspectionCount,
                    'action' => $action,
                    'is_completed' => $remaining === 0 && $awaiting === 0 && $problem === 0 && $purchased > 0,
                    'latest_activity_at' => collect([$rows->max('latest_purchase_event_at'), $rows->max('latest_arrival_at')])->filter()->max(),
                    'items' => $rows->values(),
                ];
            })
            ->sortByDesc(fn ($order) => is_numeric($order['order_number']) ? (int) $order['order_number'] : 0)
            ->values();
    }

    private function customerLabel(object $row): string
    {
        $name = trim((string) ($row->bill_to_name ?? ''));
        $company = trim((string) ($row->bill_to_company ?? ''));

        if ($company !== '' && $name !== '') {
            return $company . ' / ' . $name;
        }

        return $company !== '' ? $company : ($name !== '' ? $name : 'Unknown customer');
    }

    private function tabs(array $summary): array
    {
        return [
            'to_buy' => ['label' => 'To Purchase', 'count' => $summary['to_buy']],
            'purchases' => ['label' => 'Purchased', 'count' => $summary['purchases'] ?? 0],
            'problems' => ['label' => 'Problems', 'count' => $summary['problems']],
        ];
    }

    private function paymentOptions(): array
    {
        return [
            'paid_or_part' => 'Paid & Part Paid',
            'all' => 'All Orders',
        ];
    }

    private function matchesTab(array $order, string $tab): bool
    {
        return match ($tab) {
            'to_buy' => $this->isToBuyOrder($order),
            'awaiting_arrival' => $this->isAwaitingArrivalOrder($order),
            'problems' => $this->isProblemOrder($order),
            'completed' => $this->isCompletedOrder($order),
            'purchases' => false,
            default => true,
        };
    }

    /**
     * Purchasing queue priority:
     *
     * 1. Problems first: sourcing issues or failed purchase decisions must not
     *    sit in the plain To Buy queue.
     * 2. To Buy second: only clean buyable orders belong here.
     * 3. Awaiting Arrival third: already bought and waiting for goods.
     * 4. Completed last.
     */
    private function isProblemOrder(array $order): bool
    {
        return (int) ($order['problem_qty'] ?? 0) > 0;
    }

    private function isToBuyOrder(array $order): bool
    {
        return ! $this->isProblemOrder($order)
            && (int) ($order['remaining_to_buy_qty'] ?? 0) > 0;
    }

    private function isAwaitingArrivalOrder(array $order): bool
    {
        return ! $this->isProblemOrder($order)
            && ! $this->isToBuyOrder($order)
            && (int) ($order['awaiting_arrival_qty'] ?? 0) > 0;
    }

    private function isCompletedOrder(array $order): bool
    {
        return ! $this->isProblemOrder($order)
            && ! $this->isToBuyOrder($order)
            && ! $this->isAwaitingArrivalOrder($order)
            && (bool) ($order['is_completed'] ?? false);
    }

    private function matchesPayment(string $status, string $payment): bool
    {
        return match ($payment) {
            'paid_or_part' => in_array($status, ['paid', 'part_paid'], true),
            'all' => true,
            default => $status === $payment,
        };
    }

    private function normaliseTab(string $tab): string
    {
        return in_array($tab, ['to_buy', 'purchases', 'problems'], true) ? $tab : 'to_buy';
    }

    private function normalisePayment(string $payment): string
    {
        return in_array($payment, ['paid_or_part', 'all'], true) ? $payment : 'paid_or_part';
    }
}
