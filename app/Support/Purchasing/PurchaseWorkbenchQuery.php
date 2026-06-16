<?php

namespace App\Support\Purchasing;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseWorkbenchQuery
{
    /**
     * Problem statuses that can return to the To Buy queue only while the item
     * is still genuinely being sourced again. Once staff choose a terminal
     * resolution such as refund/remove/replace, they must no longer behave as
     * purchase demand.
     */
    private const RESOURCING_PROBLEM_STATUSES = ['unfulfilled', 'unavailable'];

    /**
     * Resolution actions that mean purchasing work is no longer required for
     * that failed attempt. These records remain in problem/history views but
     * must not create ghost To Buy quantities.
     */
    private const TERMINAL_RESOLUTION_ACTIONS = [
        'refund_required',
        'refunded',
        'refund_to_wallet',
        'removed',
        'replaced',
        'replaced_via_amendment',
        'customer_declined',
        'cancelled',
        'closed',
        'no_rebuy',
    ];

    private const TERMINAL_RESOLUTION_STATUSES = ['resolved', 'closed', 'cancelled'];

    public function __construct(private readonly PurchaseProgressSummary $progressSummary)
    {
    }

    public function forOrder(int $orderId): array
    {
        return [
            'summary' => $this->progressSummary->forOrder($orderId),
            'items' => $this->itemsForOrder($orderId),
            'retailer_groups' => $this->retailerGroupsForOrder($orderId),
            'purchases' => $this->purchasesForOrder($orderId),
        ];
    }

    public function itemsForOrder(int $orderId): Collection
    {
        return $this->basePurchaseItemQuery()
            ->where('order_items.order_id', $orderId)
            ->orderBy('order_items.sort_order')
            ->orderBy('order_items.id')
            ->get()
            ->map(fn ($item) => $this->decorateItem($item));
    }

    public function retailerGroupsForOrder(int $orderId): Collection
    {
        return $this->itemsForOrder($orderId)
            ->groupBy(fn ($item) => $item->retailer_group_key)
            ->map(function (Collection $items) {
                $first = $items->first();

                return (object) [
                    'key' => $first->retailer_group_key,
                    'name' => $first->retailer_display_name,
                    'host' => $first->retailer_host,
                    'item_count' => $items->count(),
                    'total_qty' => (int) $items->sum('quantity'),
                    'purchased_qty' => (int) $items->sum('purchased_qty'),
                    'problem_qty' => (int) $items->sum('problem_qty'),
                    'remaining_qty' => (int) $items->sum('purchase_remaining_qty'),
                    'arrived_qty' => (int) $items->sum('arrived_qty'),
                    'line_total' => (float) $items->sum('line_total'),
                    'items' => $items->values(),
                ];
            })
            ->sortBy('name')
            ->values();
    }

    public function purchasesForOrder(int $orderId): Collection
    {
        return DB::table('order_item_purchases')
            ->join('order_items', 'order_items.id', '=', 'order_item_purchases.order_item_id')
            ->select([
                'order_item_purchases.id',
                'order_item_purchases.order_item_id',
                'order_item_purchases.root_item_id',
                'order_item_purchases.order_id',
                'order_items.item_name',
                'order_items.requires_inspection',
                'order_items.inspection_note',
                'order_item_purchases.qty',
                'order_item_purchases.status',
                'order_item_purchases.purchase_unit_price',
                'order_item_purchases.purchase_line_total',
                'order_item_purchases.currency',
                'order_item_purchases.marketplace_seller',
                'order_item_purchases.retailer_order_reference',
                'order_item_purchases.note',
                'order_item_purchases.problem_code',
                'order_item_purchases.problem_notes',
                'order_item_purchases.resolution_action',
                'order_item_purchases.resolution_status',
                'order_item_purchases.ordered_at',
                'order_item_purchases.expected_dispatch_at',
                'order_item_purchases.expected_uk_hub_at',
                'order_item_purchases.expected_gibraltar_at',
                'order_item_purchases.received_at',
                'order_item_purchases.cancelled_at',
                'order_item_purchases.requires_marking_attention',
                'order_item_purchases.internal_notes',
                'order_item_purchases.created_at',
            ])
            ->where('order_item_purchases.order_id', $orderId)
            ->whereNull('order_item_purchases.cancelled_at')
            ->orderByDesc('order_item_purchases.created_at')
            ->limit(80)
            ->get();
    }

    public function deskSummary(array $filters = []): array
    {
        $summaryFilters = $filters;
        unset($summaryFilters['tab']);
        $items = $this->deskItems(array_merge($summaryFilters, ['status' => 'all', 'limit' => 20000]));

        return [
            'visible_order_count' => $items->pluck('order_id')->unique()->count(),
            'to_buy_qty' => (int) $items->sum('purchase_remaining_qty'),
            'to_buy_orders' => $items->filter(fn ($item) => $item->purchase_remaining_qty > 0)->pluck('order_id')->unique()->count(),
            'problem_qty' => (int) $items->sum('problem_qty'),
            'problem_orders' => $items->filter(fn ($item) => $item->problem_qty > 0)->pluck('order_id')->unique()->count(),
            'awaiting_arrival_qty' => (int) $items->sum('arrival_remaining_qty'),
            'awaiting_arrival_orders' => $items->filter(fn ($item) => $item->arrival_remaining_qty > 0)->pluck('order_id')->unique()->count(),
        ];
    }

    public function pendingItemsForPurchasingDesk(array $filters = []): Collection
    {
        return $this->deskItems($filters + ['status' => 'to_buy']);
    }

    public function orderGroupsForPurchasingDesk(array $filters = []): Collection
    {
        $items = $this->deskItems($filters);

        return $items
            ->groupBy('order_id')
            ->map(function (Collection $orderItems) {
                $first = $orderItems->first();
                $retailerGroups = $orderItems
                    ->groupBy(fn ($item) => $item->retailer_group_key)
                    ->map(function (Collection $retailerItems) {
                        $firstRetailer = $retailerItems->first();

                        return (object) [
                            'key' => $firstRetailer->retailer_group_key,
                            'name' => $firstRetailer->retailer_display_name,
                            'host' => $firstRetailer->retailer_host,
                            'items' => $retailerItems->values(),
                            'pending_qty' => (int) $retailerItems->sum('purchase_remaining_qty'),
                            'problem_qty' => (int) $retailerItems->sum('problem_qty'),
                            'awaiting_arrival_qty' => (int) $retailerItems->sum('arrival_remaining_qty'),
                        ];
                    })
                    ->sortBy('name')
                    ->values();

                return (object) [
                    'order_id' => (int) $first->order_id,
                    'order_number' => $first->order_number,
                    'customer_name' => $first->bill_to_company ?: $first->bill_to_name,
                    'purchase_mode' => $first->purchase_mode,
                    'payment_status' => $first->payment_status,
                    'settled_amount' => (float) $first->settled_amount,
                    'grand_total' => (float) $first->grand_total,
                    'item_count' => $orderItems->count(),
                    'requested_qty' => (int) $orderItems->sum('quantity'),
                    'pending_qty' => (int) $orderItems->sum('purchase_remaining_qty'),
                    'purchased_qty' => (int) $orderItems->sum('purchased_qty'),
                    'problem_qty' => (int) $orderItems->sum('problem_qty'),
                    'arrived_qty' => (int) $orderItems->sum('arrived_qty'),
                    'awaiting_arrival_qty' => (int) $orderItems->sum('arrival_remaining_qty'),
                    'retailer_count' => $retailerGroups->count(),
                    'retailer_groups' => $retailerGroups,
                ];
            })
            ->sortByDesc(fn ($order) => (int) $order->order_number)
            ->values();
    }

    public function recentPurchaseEvents(array $filters = []): Collection
    {
        $query = DB::table('order_item_purchases')
            ->join('orders', 'orders.id', '=', 'order_item_purchases.order_id')
            ->join('order_items', 'order_items.id', '=', 'order_item_purchases.order_item_id')
            ->leftJoin('retailers', 'retailers.id', '=', 'order_item_purchases.retailer_id')
            ->select([
                'order_item_purchases.id',
                'order_item_purchases.order_id',
                'orders.order_number',
                'orders.bill_to_name',
                'orders.bill_to_company',
                'order_items.item_name',
                'retailers.name as retailer_name',
                'order_item_purchases.qty',
                'order_item_purchases.status',
                'order_item_purchases.problem_code',
                'order_item_purchases.problem_notes',
                'order_item_purchases.resolution_action',
                'order_item_purchases.resolution_status',
                'order_item_purchases.marketplace_seller',
                'order_item_purchases.retailer_order_reference',
                'order_item_purchases.note',
                'order_item_purchases.internal_notes',
                'order_item_purchases.ordered_at',
                'order_item_purchases.expected_uk_hub_at',
                'order_item_purchases.cancelled_at',
                'order_item_purchases.created_at',
            ])
            ->whereNull('order_item_purchases.cancelled_at')
            ->orderByDesc('order_item_purchases.created_at')
            ->limit(30);

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
            $query->where(function ($search) use ($like) {
                $search->where('orders.order_number', 'like', $like)
                    ->orWhere('orders.bill_to_name', 'like', $like)
                    ->orWhere('orders.bill_to_company', 'like', $like)
                    ->orWhere('order_items.item_name', 'like', $like)
                    ->orWhere('retailers.name', 'like', $like)
                    ->orWhere('order_item_purchases.retailer_order_reference', 'like', $like);
            });
        }

        return $query->get();
    }

    private function deskItems(array $filters = []): Collection
    {
        $status = $filters['tab'] ?? $filters['status'] ?? 'to_buy';
        $payment = $filters['payment'] ?? 'paid';
        $query = $this->basePurchaseItemQuery();

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
            $query->where(function ($search) use ($like) {
                $search->where('orders.order_number', 'like', $like)
                    ->orWhere('orders.bill_to_name', 'like', $like)
                    ->orWhere('orders.bill_to_company', 'like', $like)
                    ->orWhere('order_items.item_name', 'like', $like)
                    ->orWhere('order_items.description', 'like', $like)
                    ->orWhere('order_items.product_code', 'like', $like)
                    ->orWhere('order_items.product_url', 'like', $like)
                    ->orWhere('retailers.name', 'like', $like)
                    ->orWhere('order_retailers.retailer_name', 'like', $like)
                    ->orWhere('purchase_totals.latest_retailer_order_reference', 'like', $like);
            });
        }

        $items = $query
            ->orderByRaw('CAST(orders.order_number AS UNSIGNED) DESC')
            ->orderBy('retailers.name')
            ->orderBy('order_items.sort_order')
            ->limit(20000)
            ->get()
            ->map(fn ($item) => $this->decorateItem($item));

        if ($payment !== 'all') {
            $items = $items->filter(fn ($item) => $item->payment_status === $payment)->values();
        }

        $filtered = match ($status) {
            'problems' => $items->filter(fn ($item) => $item->problem_qty > 0 && $item->purchase_remaining_qty < 1)->values(),
            'awaiting_arrival' => $items->filter(fn ($item) => $item->arrival_remaining_qty > 0)->values(),
            'all' => $items,
            default => $items->filter(fn ($item) => $item->purchase_remaining_qty > 0)->values(),
        };

        return $filtered->take((int) ($filters['limit'] ?? 500))->values();
    }

    private function basePurchaseItemQuery()
    {
        $purchased = $this->sqlIn(PurchaseProgressSummary::PURCHASED_STATUSES);
        $problems = $this->sqlIn(PurchaseProgressSummary::PROBLEM_STATUSES);
        $resourcingProblems = $this->sqlIn(self::RESOURCING_PROBLEM_STATUSES);
        $terminalResolutionActions = $this->sqlIn(self::TERMINAL_RESOLUTION_ACTIONS);
        $terminalResolutionStatuses = $this->sqlIn(self::TERMINAL_RESOLUTION_STATUSES);

        // V2 rule: purchasing and arrival history belongs to the root item
        // lineage, not the current order_item_id/order_id. Amendments create
        // new order item rows, but the same logical item keeps root_item_id.
        $purchaseTotals = DB::table('order_item_purchases')
            ->select([
                DB::raw('COALESCE(root_item_id, order_item_id) as root_item_id'),
                DB::raw("SUM(CASE WHEN status IN ($purchased) AND cancelled_at IS NULL THEN qty ELSE 0 END) as purchased_qty"),
                // All problem events, including cancelled/refund-required ones,
                // reduce expected arrival. A supplier-cancelled item should not
                // remain on Arrival/Marking just because it was once purchased.
                DB::raw("SUM(CASE WHEN status IN ($problems) THEN qty ELSE 0 END) as problem_qty"),
                // Hard problem qty blocks To Buy. Resourcing problems only stay
                // buyable while they are open and not terminally resolved.
                DB::raw("SUM(CASE
                    WHEN status IN ($problems)
                     AND (
                        status NOT IN ($resourcingProblems)
                        OR cancelled_at IS NOT NULL
                        OR COALESCE(resolution_action, '') IN ($terminalResolutionActions)
                        OR COALESCE(resolution_status, '') IN ($terminalResolutionStatuses)
                     )
                    THEN qty ELSE 0 END) as hard_problem_qty"),
                DB::raw("SUM(CASE
                    WHEN status IN ($resourcingProblems)
                     AND cancelled_at IS NULL
                     AND COALESCE(resolution_action, '') NOT IN ($terminalResolutionActions)
                     AND COALESCE(resolution_status, '') NOT IN ($terminalResolutionStatuses)
                    THEN qty ELSE 0 END) as resourcing_qty"),
                DB::raw('MAX(retailer_order_reference) as latest_retailer_order_reference'),
                DB::raw('MAX(expected_uk_hub_at) as latest_expected_uk_hub_at'),
                DB::raw('MAX(marketplace_seller) as latest_marketplace_seller'),
                DB::raw('MAX(created_at) as latest_purchase_event_at'),
                DB::raw('COUNT(*) as purchase_event_count'),
            ])
            ->groupBy(DB::raw('COALESCE(root_item_id, order_item_id)'));

        $arrivalTotals = DB::table('purchase_arrival_assignments')
            ->select([
                DB::raw('COALESCE(root_item_id, order_item_id) as root_item_id'),
                DB::raw('SUM(CASE WHEN undone_at IS NULL THEN qty ELSE 0 END) as arrived_qty'),
                DB::raw('MAX(status) as latest_arrival_status'),
                DB::raw('MAX(matched_at) as latest_matched_at'),
            ])
            ->groupBy(DB::raw('COALESCE(root_item_id, order_item_id)'));

        $settlementTotals = DB::table('order_transactions')
            ->select([
                'order_id',
                DB::raw("SUM(CASE
                    WHEN status = 'recorded' AND type IN ('payment', 'credit_application') THEN amount
                    WHEN status = 'recorded' AND type IN ('payment_void', 'credit_application_void', 'refund') THEN -ABS(amount)
                    WHEN status = 'recorded' AND type = 'refund_void' THEN ABS(amount)
                    ELSE 0
                END) as settled_amount"),
            ])
            ->groupBy('order_id');

        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('order_retailers', 'order_retailers.id', '=', 'order_items.order_retailer_id')
            ->leftJoin('retailers', 'retailers.id', '=', 'order_retailers.retailer_id')
            ->leftJoinSub($purchaseTotals, 'purchase_totals', function ($join) {
                $join->on('purchase_totals.root_item_id', '=', DB::raw('COALESCE(order_items.root_item_id, order_items.id)'));
            })
            ->leftJoinSub($arrivalTotals, 'arrival_totals', function ($join) {
                $join->on('arrival_totals.root_item_id', '=', DB::raw('COALESCE(order_items.root_item_id, order_items.id)'));
            })
            ->leftJoinSub($settlementTotals, 'settlement_totals', function ($join) {
                $join->on('settlement_totals.order_id', '=', 'orders.id');
            })
            ->whereNull('orders.cancelled_at')
            ->whereNull('orders.completed_at')
            ->where(function ($query) {
                $query->whereNull('orders.purchase_mode')
                    ->orWhere('orders.purchase_mode', '!=', 'customer_self_purchase');
            })
            ->where(function ($query) {
                $query->whereNull('orders.status')
                    ->orWhereNotIn('orders.status', ['cancelled', 'completed', 'superseded']);
            })
            // Latest active order version only. Historical parent versions stay
            // auditable but are not live purchasing workspaces.
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('orders as child_orders')
                    ->whereColumn('child_orders.parent_order_id', 'orders.id')
                    ->whereNull('child_orders.cancelled_at')
                    ->where(function ($child) {
                        $child->whereNull('child_orders.status')
                            ->orWhereNotIn('child_orders.status', ['cancelled', 'superseded']);
                    });
            })
            ->select([
                'order_items.id',
                'order_items.order_id as item_order_id',
                DB::raw('COALESCE(order_items.root_item_id, order_items.id) as root_item_id'),
                'order_items.item_name',
                'order_items.description',
                'order_items.product_code',
                'order_items.product_url',
                'order_items.marketplace_seller',
                'order_items.quantity as quantity',
                'order_items.quantity as order_item_quantity',
                'order_items.unit_price',
                'order_items.line_total',
                'order_items.item_retailer_delivery_fee',
                'order_items.retailer_delivery_allocated',
                'order_items.dabba_fee_allocated',
                'order_items.status',
                'order_items.requires_inspection',
                'order_items.inspection_note',
                'order_items.retailer_order_reference',
                'order_items.tracking_reference',
                'order_items.ordered_at',
                'order_items.arrived_at',
                'order_retailers.retailer_id',
                'order_retailers.retailer_name as order_retailer_name',
                'order_retailers.retailer_base_url as order_retailer_base_url',
                'retailers.name as master_retailer_name',
                'retailers.base_url as master_retailer_base_url',
                DB::raw('COALESCE(purchase_totals.purchased_qty, 0) as purchased_qty'),
                DB::raw('COALESCE(purchase_totals.problem_qty, 0) as problem_qty'),
                DB::raw('COALESCE(purchase_totals.hard_problem_qty, 0) as hard_problem_qty'),
                DB::raw('COALESCE(purchase_totals.resourcing_qty, 0) as resourcing_qty'),
                DB::raw('COALESCE(arrival_totals.arrived_qty, 0) as arrived_qty'),
                'purchase_totals.latest_retailer_order_reference',
                'purchase_totals.latest_expected_uk_hub_at',
                'purchase_totals.latest_marketplace_seller',
                'purchase_totals.latest_purchase_event_at',
                'purchase_totals.purchase_event_count',
                'arrival_totals.latest_arrival_status',
                'arrival_totals.latest_matched_at',
                'orders.id as order_id',
                'orders.order_number',
                'orders.purchase_mode',
                'orders.bill_to_name',
                'orders.bill_to_company',
                'orders.grand_total',
                DB::raw('COALESCE(settlement_totals.settled_amount, 0) as settled_amount'),
                DB::raw('CASE WHEN COALESCE(settlement_totals.settled_amount,0) >= orders.grand_total AND orders.grand_total > 0 THEN "paid" WHEN COALESCE(settlement_totals.settled_amount,0) > 0 THEN "part_paid" ELSE "unpaid" END as payment_status'),
            ]);
    }

    private function decorateItem(object $item): object
    {
        $host = $this->hostFromUrl((string) ($item->master_retailer_base_url ?: $item->order_retailer_base_url ?: $item->product_url));
        $retailerName = trim((string) ($item->master_retailer_name ?: $item->order_retailer_name));
        $seller = trim((string) ($item->latest_marketplace_seller ?: $item->marketplace_seller));

        $item->retailer_host = $host ?: $this->hostFromUrl((string) $item->product_url);
        $item->retailer_display_name = $retailerName ?: ($seller ?: ($item->retailer_host ?: 'Unknown retailer'));
        $item->retailer_group_key = $item->retailer_id ? 'retailer-' . (int) $item->retailer_id : Str::slug($item->retailer_display_name ?: 'unknown-retailer');
        $item->quantity = max(0, (int) $item->quantity);
        $item->purchased_qty = max(0, (int) $item->purchased_qty);
        $item->problem_qty = max(0, (int) ($item->problem_qty ?? 0));
        $item->hard_problem_qty = max(0, (int) ($item->hard_problem_qty ?? 0));
        $item->resourcing_qty = max(0, (int) ($item->resourcing_qty ?? 0));
        $item->arrived_qty = max(0, (int) ($item->arrived_qty ?? 0));
        $item->purchase_remaining_qty = max(0, $item->quantity - $item->purchased_qty - $item->hard_problem_qty);
        $item->expected_arrival_qty = max(0, $item->purchased_qty - $item->problem_qty);
        $item->arrival_remaining_qty = max(0, $item->purchased_qty - $item->problem_qty - $item->arrived_qty);

        return $item;
    }

    private function sqlIn(array $values): string
    {
        return collect($values)
            ->map(fn ($value) => "'" . str_replace("'", "''", $value) . "'")
            ->implode(',');
    }

    private function hostFromUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (! $host && ! str_starts_with($url, 'http')) {
            $host = parse_url('https://' . $url, PHP_URL_HOST);
        }

        $host = strtolower((string) $host);

        return preg_replace('/^www\./', '', $host) ?: '';
    }
}
