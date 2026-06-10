<?php

namespace App\Support\Purchasing;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseWorkbenchQuery
{
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
        $purchaseSubquery = DB::table('order_item_purchases')
            ->select(
                'root_item_id',
                DB::raw('SUM(CASE WHEN status IN (\'' . implode("','", PurchaseProgressSummary::PURCHASED_STATUSES) . '\') AND cancelled_at IS NULL THEN qty ELSE 0 END) as purchased_qty'),
                DB::raw('SUM(CASE WHEN status IN (\'' . implode("','", PurchaseProgressSummary::PROBLEM_STATUSES) . '\') AND cancelled_at IS NULL THEN qty ELSE 0 END) as problem_qty'),
                DB::raw('MAX(retailer_order_reference) as latest_retailer_order_reference'),
                DB::raw('MAX(expected_uk_hub_at) as latest_expected_uk_hub_at'),
                DB::raw('MAX(marketplace_seller) as latest_marketplace_seller')
            )
            ->groupBy('root_item_id');

        $arrivalSubquery = DB::table('purchase_arrival_assignments')
            ->select(
                'root_item_id',
                DB::raw('SUM(CASE WHEN undone_at IS NULL THEN qty ELSE 0 END) as arrived_qty'),
                DB::raw('MAX(status) as latest_arrival_status'),
                DB::raw('MAX(matched_at) as latest_matched_at')
            )
            ->groupBy('root_item_id');

        return DB::table('order_items')
            ->leftJoin('order_retailers', 'order_retailers.id', '=', 'order_items.order_retailer_id')
            ->leftJoin('retailers', 'retailers.id', '=', 'order_retailers.retailer_id')
            ->leftJoinSub($purchaseSubquery, 'purchase_totals', function ($join) {
                $join->on('purchase_totals.root_item_id', '=', DB::raw('COALESCE(order_items.root_item_id, order_items.id)'));
            })
            ->leftJoinSub($arrivalSubquery, 'arrival_totals', function ($join) {
                $join->on('arrival_totals.root_item_id', '=', DB::raw('COALESCE(order_items.root_item_id, order_items.id)'));
            })
            ->select([
                'order_items.id',
                DB::raw('COALESCE(order_items.root_item_id, order_items.id) as root_item_id'),
                'order_items.item_name',
                'order_items.description',
                'order_items.product_code',
                'order_items.product_url',
                'order_items.marketplace_seller',
                'order_items.quantity',
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
                DB::raw('COALESCE(arrival_totals.arrived_qty, 0) as arrived_qty'),
                'purchase_totals.latest_retailer_order_reference',
                'purchase_totals.latest_expected_uk_hub_at',
                'purchase_totals.latest_marketplace_seller',
                'arrival_totals.latest_arrival_status',
                'arrival_totals.latest_matched_at',
            ])
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
                    'remaining_qty' => max(0, (int) $items->sum('quantity') - (int) $items->sum('purchased_qty') - (int) $items->sum('problem_qty')),
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
        $rootItemIds = $this->progressSummary->rootItemIdsForOrder($orderId);

        if ($rootItemIds->isEmpty()) {
            return collect();
        }

        return DB::table('order_item_purchases')
            ->join('order_items', 'order_items.id', '=', 'order_item_purchases.order_item_id')
            ->select([
                'order_item_purchases.id',
                'order_item_purchases.order_item_id',
                'order_item_purchases.root_item_id',
                'order_item_purchases.order_id',
                'order_items.item_name',
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
            ->whereIn('order_item_purchases.root_item_id', $rootItemIds->all())
            ->whereNull('order_item_purchases.cancelled_at')
            ->orderByDesc('order_item_purchases.created_at')
            ->limit(80)
            ->get();
    }

    public function pendingItemsForPurchasingDesk(array $filters = []): Collection
    {
        $query = $this->basePurchasingDeskItemsQuery($filters)
            ->where('orders.purchase_mode', '<>', 'customer_self_purchase')
            ->where(function ($statusQuery) {
                $statusQuery->whereIn('orders.status', ['paid', 'invoiced', 'created'])
                    ->orWhereNotNull('orders.paid_at');
            });

        return $query
            ->orderBy('retailers.name')
            ->orderBy('orders.order_number')
            ->orderBy('order_items.sort_order')
            ->limit((int) ($filters['limit'] ?? 250))
            ->get()
            ->map(fn ($item) => $this->decorateItem($item));
    }

    private function basePurchasingDeskItemsQuery(array $filters)
    {
        $purchaseSubquery = DB::table('order_item_purchases')
            ->select(
                'root_item_id',
                DB::raw('SUM(CASE WHEN status IN (\'' . implode("','", PurchaseProgressSummary::PURCHASED_STATUSES) . '\') AND cancelled_at IS NULL THEN qty ELSE 0 END) as purchased_qty'),
                DB::raw('SUM(CASE WHEN status IN (\'' . implode("','", PurchaseProgressSummary::PROBLEM_STATUSES) . '\') AND cancelled_at IS NULL THEN qty ELSE 0 END) as problem_qty')
            )
            ->groupBy('root_item_id');

        $query = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('order_retailers', 'order_retailers.id', '=', 'order_items.order_retailer_id')
            ->leftJoin('retailers', 'retailers.id', '=', 'order_retailers.retailer_id')
            ->leftJoinSub($purchaseSubquery, 'purchase_totals', function ($join) {
                $join->on('purchase_totals.root_item_id', '=', DB::raw('COALESCE(order_items.root_item_id, order_items.id)'));
            })
            ->select([
                'orders.id as order_id',
                'orders.order_number',
                'orders.purchase_mode',
                'orders.bill_to_name',
                'orders.bill_to_company',
                'order_items.*',
                'order_retailers.retailer_id',
                'order_retailers.retailer_name as order_retailer_name',
                'order_retailers.retailer_base_url as order_retailer_base_url',
                'retailers.name as master_retailer_name',
                'retailers.base_url as master_retailer_base_url',
                DB::raw('COALESCE(purchase_totals.purchased_qty, 0) as purchased_qty'),
                DB::raw('COALESCE(purchase_totals.problem_qty, 0) as problem_qty'),
                DB::raw('0 as arrived_qty'),
                DB::raw('NULL as latest_retailer_order_reference'),
                DB::raw('NULL as latest_expected_uk_hub_at'),
                DB::raw('NULL as latest_marketplace_seller'),
                DB::raw('NULL as latest_arrival_status'),
                DB::raw('NULL as latest_matched_at')
            ])
            ->whereNull('orders.cancelled_at')
            ->whereNull('orders.completed_at')
            ->havingRaw('(order_items.quantity - COALESCE(purchase_totals.purchased_qty, 0) - COALESCE(purchase_totals.problem_qty, 0)) > 0');

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
            $query->where(function ($search) use ($like) {
                $search->where('orders.order_number', 'like', $like)
                    ->orWhere('orders.bill_to_name', 'like', $like)
                    ->orWhere('orders.bill_to_company', 'like', $like)
                    ->orWhere('order_items.item_name', 'like', $like)
                    ->orWhere('order_items.product_code', 'like', $like)
                    ->orWhere('retailers.name', 'like', $like)
                    ->orWhere('order_retailers.retailer_name', 'like', $like);
            });
        }

        return $query;
    }

    private function decorateItem(object $item): object
    {
        $host = $this->hostFromUrl((string) ($item->master_retailer_base_url ?: $item->order_retailer_base_url ?: $item->product_url));
        $retailerName = trim((string) ($item->master_retailer_name ?: $item->order_retailer_name));
        $seller = trim((string) ($item->latest_marketplace_seller ?: $item->marketplace_seller));

        $item->retailer_host = $host ?: $this->hostFromUrl((string) $item->product_url);
        $item->retailer_display_name = $retailerName ?: ($seller ?: ($item->retailer_host ?: 'Unknown retailer'));
        $item->retailer_group_key = $item->retailer_id ? 'retailer-' . (int) $item->retailer_id : Str::slug($item->retailer_display_name ?: 'unknown-retailer');
        $item->purchased_qty = min((int) $item->quantity, (int) $item->purchased_qty);
        $item->problem_qty = min((int) $item->quantity, (int) ($item->problem_qty ?? 0));
        $item->arrived_qty = min((int) $item->quantity, (int) ($item->arrived_qty ?? 0));
        $item->purchase_remaining_qty = max(0, (int) $item->quantity - (int) $item->purchased_qty - (int) $item->problem_qty);
        $item->arrival_remaining_qty = max(0, (int) $item->quantity - (int) $item->arrived_qty);

        return $item;
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
