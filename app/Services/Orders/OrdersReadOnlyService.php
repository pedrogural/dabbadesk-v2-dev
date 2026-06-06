<?php

namespace App\Services\Orders;

use App\Support\Search\SmartSearch;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrdersReadOnlyService
{
    public function statusOptions(): Collection
    {
        $legacyStatuses = DB::table('orders')
            ->select('status')
            ->whereNotNull('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status')
            ->reject(fn ($status) => in_array((string) $status, ['paid', 'purchased', 'arrived', 'customer_self_purchase'], true))
            ->values();

        return collect([
            'paid',
            'unpaid',
            'purchased',
            'arrived',
            'customer_self_purchase',
        ])->merge($legacyStatuses)->unique()->values();
    }

    public function search(array $filters): LengthAwarePaginator
    {
        $queryText = trim((string) ($filters['q'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));
        $mineOnly = ! empty($filters['mine']);
        $showHistory = ! empty($filters['show_history']);
        $userId = (int) ($filters['user_id'] ?? 0);

        $settlementSubquery = DB::table('order_transactions')
            ->select('order_id', DB::raw('SUM(amount) as settled_total'))
            ->where('status', 'recorded')
            ->groupBy('order_id');

        $itemSubquery = DB::table('order_items')
            ->select('order_id', DB::raw('COUNT(*) as item_count'), DB::raw('SUM(quantity) as total_qty'))
            ->groupBy('order_id');

        $purchaseByRootSubquery = DB::table('order_item_purchases')
            ->select('root_item_id', DB::raw('SUM(qty) as purchased_qty'))
            ->whereIn('status', ['purchased', 'ordered', 'received'])
            ->whereNull('cancelled_at')
            ->whereNotNull('root_item_id')
            ->groupBy('root_item_id');

        $purchaseSubquery = DB::table('order_items')
            ->leftJoinSub($purchaseByRootSubquery, 'purchase_by_root', function ($join) {
                $join->on('purchase_by_root.root_item_id', '=', DB::raw('COALESCE(order_items.root_item_id, order_items.id)'));
            })
            ->select('order_items.order_id', DB::raw('SUM(LEAST(order_items.quantity, COALESCE(purchase_by_root.purchased_qty, 0))) as purchased_qty'))
            ->groupBy('order_items.order_id');

        $arrivalByRootSubquery = DB::table('purchase_arrival_assignments')
            ->select('root_item_id', DB::raw('SUM(qty) as arrived_qty'))
            ->whereNull('undone_at')
            ->whereNotNull('root_item_id')
            ->groupBy('root_item_id');

        $arrivalSubquery = DB::table('order_items')
            ->leftJoinSub($arrivalByRootSubquery, 'arrival_by_root', function ($join) {
                $join->on('arrival_by_root.root_item_id', '=', DB::raw('COALESCE(order_items.root_item_id, order_items.id)'));
            })
            ->select('order_items.order_id', DB::raw('SUM(LEAST(order_items.quantity, COALESCE(arrival_by_root.arrived_qty, 0))) as arrived_qty'))
            ->groupBy('order_items.order_id');

        return DB::table('orders')
            ->join('draft_orders', 'draft_orders.id', '=', 'orders.draft_order_id')
            ->join('customers', 'customers.id', '=', 'draft_orders.customer_id')
            ->leftJoin('users as created_user', 'created_user.id', '=', 'orders.created_by_user_id')
            ->leftJoin('users as updated_user', 'updated_user.id', '=', 'orders.updated_by_user_id')
            ->leftJoinSub($settlementSubquery, 'settlement_totals', function ($join) {
                $join->on('settlement_totals.order_id', '=', 'orders.id');
            })
            ->leftJoinSub($itemSubquery, 'item_totals', function ($join) {
                $join->on('item_totals.order_id', '=', 'orders.id');
            })
            ->leftJoinSub($purchaseSubquery, 'purchase_totals', function ($join) {
                $join->on('purchase_totals.order_id', '=', 'orders.id');
            })
            ->leftJoinSub($arrivalSubquery, 'arrival_totals', function ($join) {
                $join->on('arrival_totals.order_id', '=', 'orders.id');
            })
            ->select([
                'orders.id',
                'orders.draft_order_id',
                'orders.order_number',
                'orders.status',
                'orders.purchase_mode',
                'orders.grand_total',
                'orders.bill_to_name',
                'orders.bill_to_email',
                'orders.created_at',
                'orders.created_by_user_id',
                'orders.updated_by_user_id',
                'orders.paid_at',
                'orders.purchased_at',
                'orders.completed_at',
                'orders.parent_order_id',
                'orders.cancel_reason',
                'customers.id as customer_id',
                'customers.first_name',
                'customers.last_name',
                'customers.company_name',
                'created_user.name as created_by_name',
                'updated_user.name as updated_by_name',
                DB::raw('COALESCE(item_totals.item_count, 0) as item_count'),
                DB::raw('COALESCE(item_totals.total_qty, 0) as total_qty'),
                DB::raw('COALESCE(purchase_totals.purchased_qty, 0) as purchased_qty'),
                DB::raw('COALESCE(arrival_totals.arrived_qty, 0) as arrived_qty'),
                DB::raw('COALESCE(settlement_totals.settled_total, 0) as settled_total'),
                DB::raw('GREATEST(orders.grand_total - COALESCE(settlement_totals.settled_total, 0), 0) as balance_due'),
                DB::raw("(SELECT COUNT(*) FROM orders as revision_orders WHERE revision_orders.order_number = orders.order_number AND revision_orders.id <= orders.id) as revision_number"),
                DB::raw("(SELECT COUNT(*) FROM orders as revision_orders WHERE revision_orders.order_number = orders.order_number) as revision_total"),
                DB::raw("CASE WHEN orders.status = 'superseded' OR orders.cancel_reason = 'superseded' OR EXISTS (SELECT 1 FROM orders newer_orders WHERE newer_orders.order_number = orders.order_number AND newer_orders.id > orders.id AND newer_orders.status != 'superseded' AND (newer_orders.cancel_reason IS NULL OR newer_orders.cancel_reason != 'superseded')) THEN 'superseded' WHEN (SELECT COUNT(*) FROM orders revision_orders WHERE revision_orders.order_number = orders.order_number) > 1 THEN 'current_revision' ELSE 'current' END as revision_state"),
            ])
            ->when(! $showHistory, function ($query) {
                $query
                    ->where('orders.status', '!=', 'superseded')
                    ->where(function ($activeQuery) {
                        $activeQuery->whereNull('orders.cancel_reason')->orWhere('orders.cancel_reason', '!=', 'superseded');
                    })
                    ->whereNotExists(function ($newerQuery) {
                        $newerQuery
                            ->select(DB::raw(1))
                            ->from('orders as newer_orders')
                            ->whereColumn('newer_orders.order_number', 'orders.order_number')
                            ->whereColumn('newer_orders.id', '>', 'orders.id')
                            ->where('newer_orders.status', '!=', 'superseded')
                            ->where(function ($newerActive) {
                                $newerActive->whereNull('newer_orders.cancel_reason')->orWhere('newer_orders.cancel_reason', '!=', 'superseded');
                            });
                    });
            })
            ->when($status !== '', function ($query) use ($status) {
                match ($status) {
                    'paid' => $query->whereRaw('GREATEST(orders.grand_total - COALESCE(settlement_totals.settled_total, 0), 0) <= 0.004'),
                    'unpaid' => $query->whereRaw('GREATEST(orders.grand_total - COALESCE(settlement_totals.settled_total, 0), 0) > 0.004'),
                    'purchased' => $query
                        ->whereRaw('COALESCE(item_totals.total_qty, 0) > 0')
                        ->whereRaw('COALESCE(purchase_totals.purchased_qty, 0) >= COALESCE(item_totals.total_qty, 0)'),
                    'arrived' => $query
                        ->whereRaw('COALESCE(item_totals.total_qty, 0) > 0')
                        ->whereRaw('COALESCE(arrival_totals.arrived_qty, 0) >= COALESCE(item_totals.total_qty, 0)'),
                    'customer_self_purchase' => $query->where('orders.purchase_mode', 'customer_self_purchase'),
                    default => $query->where('orders.status', $status),
                };
            })
            ->when($mineOnly && $userId > 0, function ($query) use ($userId) {
                $query->where('orders.created_by_user_id', $userId);
            })
            ->when($queryText !== '', function ($query) use ($queryText) {
                SmartSearch::apply($query, $queryText, function ($subQuery, SmartSearch $search) {
                    $like = $search->phraseLike();

                    $subQuery
                        ->where('orders.order_number', 'like', $like)
                        ->orWhere('orders.bill_to_name', 'like', $like)
                        ->orWhere('orders.bill_to_email', 'like', $like)
                        ->orWhere('orders.bill_to_phone', 'like', $like)
                        ->orWhere('customers.first_name', 'like', $like)
                        ->orWhere('customers.last_name', 'like', $like)
                        ->orWhere('customers.company_name', 'like', $like)
                        ->orWhereRaw("CONCAT_WS(' ', customers.first_name, customers.last_name) like ?", [$like])
                        ->orWhereRaw("CONCAT_WS(' ', customers.last_name, customers.first_name) like ?", [$like]);

                    $search->orWhereAllTokensAcross($subQuery, [
                        'orders.bill_to_name',
                        'orders.bill_to_email',
                        'customers.first_name',
                        'customers.last_name',
                        'customers.company_name',
                    ]);

                    if ($search->digits !== '') {
                        $digitsLike = $search->digitsLike();
                        $subQuery->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(orders.bill_to_phone, '+', ''), ' ', ''), '-', ''), '(', ''), ')', '') like ?", [$digitsLike]);
                    }

                    $subQuery
                        ->orWhereExists(function ($itemQuery) use ($like, $search) {
                            $itemQuery
                                ->select(DB::raw(1))
                                ->from('order_items')
                                ->whereColumn('order_items.order_id', 'orders.id')
                                ->where(function ($itemSearch) use ($like, $search) {
                                    $itemSearch
                                        ->where('order_items.item_name', 'like', $like)
                                        ->orWhere('order_items.product_code', 'like', $like)
                                        ->orWhere('order_items.retailer_order_reference', 'like', $like)
                                        ->orWhere('order_items.tracking_reference', 'like', $like);

                                    $search->orWhereAllTokensAcross($itemSearch, [
                                        'order_items.item_name',
                                        'order_items.product_code',
                                        'order_items.retailer_order_reference',
                                        'order_items.tracking_reference',
                                    ]);
                                });
                        })
                        ->orWhereExists(function ($purchaseQuery) use ($like) {
                            $purchaseQuery
                                ->select(DB::raw(1))
                                ->from('order_items as purchase_lookup_items')
                                ->whereColumn('purchase_lookup_items.order_id', 'orders.id')
                                ->whereExists(function ($purchaseMatch) use ($like) {
                                    $purchaseMatch
                                        ->select(DB::raw(1))
                                        ->from('order_item_purchases')
                                        ->whereRaw('order_item_purchases.root_item_id = COALESCE(purchase_lookup_items.root_item_id, purchase_lookup_items.id)')
                                        ->where(function ($purchaseSearch) use ($like) {
                                            $purchaseSearch
                                                ->where('order_item_purchases.retailer_order_reference', 'like', $like)
                                                ->orWhere('order_item_purchases.marketplace_seller', 'like', $like);
                                        });
                                });
                        });
                });
            })
            ->orderByDesc('orders.created_at')
            ->paginate(20)
            ->withQueryString();
    }

    public function find(int $orderId): ?object
    {
        return DB::table('orders')
            ->join('draft_orders', 'draft_orders.id', '=', 'orders.draft_order_id')
            ->join('customers', 'customers.id', '=', 'draft_orders.customer_id')
            ->select([
                'orders.id',
                'orders.draft_order_id',
                'orders.source_draft_order_id',
                'orders.parent_order_id',
                DB::raw("(SELECT COUNT(*) FROM orders as revision_orders WHERE revision_orders.order_number = orders.order_number AND revision_orders.id <= orders.id) as revision_number"),
                DB::raw("(SELECT COUNT(*) FROM orders as revision_orders WHERE revision_orders.order_number = orders.order_number) as revision_total"),
                DB::raw("CASE WHEN orders.status = 'superseded' OR orders.cancel_reason = 'superseded' OR EXISTS (SELECT 1 FROM orders newer_orders WHERE newer_orders.order_number = orders.order_number AND newer_orders.id > orders.id AND newer_orders.status != 'superseded' AND (newer_orders.cancel_reason IS NULL OR newer_orders.cancel_reason != 'superseded')) THEN 'superseded' WHEN (SELECT COUNT(*) FROM orders revision_orders WHERE revision_orders.order_number = orders.order_number) > 1 THEN 'current_revision' ELSE 'current' END as revision_state"),
                'orders.order_type',
                'orders.purchase_mode',
                'orders.order_number',
                'orders.draft_order_id',
                'orders.status',
                'orders.subtotal',
                'orders.retailer_delivery_fee_total',
                'orders.dabba_fee_amount',
                'orders.grand_total',
                'orders.bill_to_name',
                'orders.bill_to_company',
                'orders.bill_to_email',
                'orders.bill_to_phone',
                'orders.bill_to_address_line1',
                'orders.bill_to_postcode',
                'orders.invoiced_at',
                'orders.sent_at',
                'orders.paid_at',
                'orders.purchased_at',
                'orders.shipped_at',
                'orders.completed_at',
                'orders.cancelled_at',
                'orders.cancel_reason',
                'orders.created_at',
                'orders.updated_at',
                'customers.id as customer_id',
                'customers.first_name',
                'customers.last_name',
                'customers.company_name',
            ])
            ->where('orders.id', $orderId)
            ->first();
    }

    public function revisionHistory(object $order): Collection
    {
        return DB::table('orders')
            ->select([
                'orders.id',
                'orders.draft_order_id',
                'orders.order_number',
                'orders.status',
                'orders.purchase_mode',
                'orders.grand_total',
                'orders.created_at',
                'orders.cancel_reason',
                DB::raw("(SELECT COUNT(*) FROM orders as revision_orders WHERE revision_orders.order_number = orders.order_number AND revision_orders.id <= orders.id) as revision_number"),
                DB::raw("(SELECT COUNT(*) FROM orders as revision_orders WHERE revision_orders.order_number = orders.order_number) as revision_total"),
                DB::raw("CASE WHEN orders.status = 'superseded' OR orders.cancel_reason = 'superseded' OR EXISTS (SELECT 1 FROM orders newer_orders WHERE newer_orders.order_number = orders.order_number AND newer_orders.id > orders.id AND newer_orders.status != 'superseded' AND (newer_orders.cancel_reason IS NULL OR newer_orders.cancel_reason != 'superseded')) THEN 'superseded' WHEN (SELECT COUNT(*) FROM orders revision_orders WHERE revision_orders.order_number = orders.order_number) > 1 THEN 'current_revision' ELSE 'current' END as revision_state"),
                DB::raw("(SELECT body FROM activity_logs WHERE activity_logs.subject_type = 'order' AND activity_logs.subject_id = orders.id AND activity_logs.type = 'system_note' ORDER BY activity_logs.id ASC LIMIT 1) as revision_note"),
            ])
            ->where('orders.order_number', $order->order_number)
            ->orderBy('orders.id')
            ->get();
    }

    public function financeSummary(int $orderId): array
    {
        $order = DB::table('orders')->where('id', $orderId)->first();

        if (! $order) {
            return [
                'order_total' => 0,
                'settled_total' => 0,
                'balance_due' => 0,
                'payments_used' => 0,
                'wallet_used' => 0,
                'refunds' => 0,
            ];
        }

        $paymentsUsed = (float) DB::table('order_transactions')
            ->where('order_id', $orderId)
            ->where('status', 'recorded')
            ->where('type', 'payment')
            ->sum('amount');

        $walletUsed = (float) DB::table('order_transactions')
            ->where('order_id', $orderId)
            ->where('status', 'recorded')
            ->where('type', 'credit_application')
            ->sum('amount');

        $refunds = (float) DB::table('order_transactions')
            ->where('order_id', $orderId)
            ->where('status', 'recorded')
            ->where('type', 'refund')
            ->sum('amount');

        $settledTotal = (float) DB::table('order_transactions')
            ->where('order_id', $orderId)
            ->where('status', 'recorded')
            ->whereIn('type', [
                'payment',
                'credit_application',
                'payment_void',
                'credit_application_void',
                'refund',
                'refund_void',
            ])
            ->sum('amount');

        $orderTotal = (float) $order->grand_total;

        return [
            'order_total' => $orderTotal,
            'settled_total' => $settledTotal,
            'balance_due' => max(0, $orderTotal - $settledTotal),
            'payments_used' => $paymentsUsed,
            'wallet_used' => $walletUsed,
            'refunds' => $refunds,
        ];
    }

    public function progressSummary(int $orderId): array
    {
        $itemQty = (int) DB::table('order_items')
            ->where('order_id', $orderId)
            ->sum('quantity');

        $rootItemIds = $this->rootItemIdsForOrder($orderId);

        if ($rootItemIds->isEmpty()) {
            return [
                'item_qty' => $itemQty,
                'purchased_qty' => 0,
                'remaining_purchase_qty' => $itemQty,
                'arrived_qty' => 0,
                'ready_qty' => 0,
                'collected_qty' => 0,
            ];
        }

        $purchasedQty = (int) DB::table('order_item_purchases')
            ->whereIn('root_item_id', $rootItemIds->all())
            ->whereIn('status', ['purchased', 'ordered', 'received'])
            ->whereNull('cancelled_at')
            ->sum('qty');

        $arrivedQty = (int) DB::table('purchase_arrival_assignments')
            ->whereIn('root_item_id', $rootItemIds->all())
            ->whereNull('undone_at')
            ->sum('qty');

        $readyQty = (int) DB::table('purchase_arrival_assignments')
            ->whereIn('root_item_id', $rootItemIds->all())
            ->whereNull('undone_at')
            ->whereIn('status', ['ready_for_collection', 'for_delivery'])
            ->sum('qty');

        $collectedQty = (int) DB::table('purchase_arrival_assignments')
            ->whereIn('root_item_id', $rootItemIds->all())
            ->whereNull('undone_at')
            ->whereIn('status', ['collected', 'delivered'])
            ->sum('qty');

        return [
            'item_qty' => $itemQty,
            'purchased_qty' => min($itemQty, $purchasedQty),
            'remaining_purchase_qty' => max(0, $itemQty - $purchasedQty),
            'arrived_qty' => min($itemQty, $arrivedQty),
            'ready_qty' => min($itemQty, $readyQty),
            'collected_qty' => min($itemQty, $collectedQty),
        ];
    }

    public function itemsGroupedByRetailer(int $orderId): Collection
    {
        return $this->items($orderId)
            ->groupBy(function ($item) {
                return $item->retailer_group_key;
            })
            ->map(function (Collection $items) {
                $first = $items->first();

                return (object) [
                    'key' => $first->retailer_group_key,
                    'name' => $first->retailer_display_name,
                    'host' => $first->retailer_host,
                    'item_count' => $items->count(),
                    'total_qty' => (int) $items->sum('quantity'),
                    'purchased_qty' => (int) $items->sum('purchased_qty'),
                    'remaining_qty' => max(0, (int) $items->sum('quantity') - (int) $items->sum('purchased_qty')),
                    'arrived_qty' => (int) $items->sum('arrived_qty'),
                    'line_total' => (float) $items->sum('line_total'),
                    'items' => $items->values(),
                ];
            })
            ->sortBy('name')
            ->values();
    }

    public function items(int $orderId): Collection
    {
        $purchaseSubquery = DB::table('order_item_purchases')
            ->select(
                'root_item_id',
                DB::raw('SUM(qty) as purchased_qty'),
                DB::raw('MAX(retailer_order_reference) as latest_retailer_order_reference'),
                DB::raw('MAX(expected_uk_hub_at) as latest_expected_uk_hub_at'),
                DB::raw('MAX(marketplace_seller) as latest_marketplace_seller')
            )
            ->whereIn('status', ['purchased', 'ordered', 'received'])
            ->whereNull('cancelled_at')
            ->groupBy('root_item_id');

        $arrivalSubquery = DB::table('purchase_arrival_assignments')
            ->select(
                'root_item_id',
                DB::raw('SUM(qty) as arrived_qty'),
                DB::raw('MAX(status) as latest_arrival_status'),
                DB::raw('MAX(matched_at) as latest_matched_at')
            )
            ->whereNull('undone_at')
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
            ->map(function ($item) {
                $host = $this->hostFromUrl((string) ($item->master_retailer_base_url ?: $item->order_retailer_base_url ?: $item->product_url));
                $retailerName = trim((string) ($item->master_retailer_name ?: $item->order_retailer_name));
                $seller = trim((string) ($item->latest_marketplace_seller ?: $item->marketplace_seller));

                $item->retailer_host = $host ?: $this->hostFromUrl((string) $item->product_url);
                $item->retailer_display_name = $retailerName ?: ($seller ?: ($item->retailer_host ?: 'Unknown retailer'));
                $item->retailer_group_key = $item->retailer_id ? 'retailer-' . (int) $item->retailer_id : Str::slug($item->retailer_display_name ?: 'unknown-retailer');
                $item->purchased_qty = min((int) $item->quantity, (int) $item->purchased_qty);
                $item->arrived_qty = min((int) $item->quantity, (int) $item->arrived_qty);
                $item->purchase_remaining_qty = max(0, (int) $item->quantity - (int) $item->purchased_qty);
                $item->arrival_remaining_qty = max(0, (int) $item->quantity - (int) $item->arrived_qty);

                return $item;
            });
    }

    public function purchases(int $orderId): Collection
    {
        $rootItemIds = $this->rootItemIdsForOrder($orderId);

        if ($rootItemIds->isEmpty()) {
            return collect();
        }

        return DB::table('order_item_purchases')
            ->join('order_items', 'order_items.id', '=', 'order_item_purchases.order_item_id')
            ->select([
                'order_item_purchases.id',
                'order_item_purchases.order_item_id',
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

    public function arrivals(int $orderId): Collection
    {
        $rootItemIds = $this->rootItemIdsForOrder($orderId);

        if ($rootItemIds->isEmpty()) {
            return collect();
        }

        return DB::table('purchase_arrival_assignments')
            ->join('order_items', 'order_items.id', '=', 'purchase_arrival_assignments.order_item_id')
            ->join('order_item_purchases', 'order_item_purchases.id', '=', 'purchase_arrival_assignments.order_item_purchase_id')
            ->select([
                'purchase_arrival_assignments.id',
                'purchase_arrival_assignments.order_item_id',
                'order_items.item_name',
                'purchase_arrival_assignments.qty',
                'purchase_arrival_assignments.status',
                'purchase_arrival_assignments.matched_at',
                'purchase_arrival_assignments.status_updated_at',
                'purchase_arrival_assignments.notes',
                'purchase_arrival_assignments.undone_at',
                'order_item_purchases.retailer_order_reference',
                'order_item_purchases.requires_marking_attention',
                DB::raw("(SELECT MIN(COALESCE(al.occurred_at, al.created_at)) FROM activity_logs al WHERE al.subject_type = 'purchase_arrival_assignment' AND al.subject_id = purchase_arrival_assignments.id AND al.deleted_at IS NULL AND (al.body LIKE '%to ready_for_collection%' OR al.body LIKE '%to for_delivery%')) as informed_at"),
                DB::raw("(SELECT MAX(COALESCE(al.occurred_at, al.created_at)) FROM activity_logs al WHERE al.subject_type = 'purchase_arrival_assignment' AND al.subject_id = purchase_arrival_assignments.id AND al.deleted_at IS NULL AND (al.body LIKE '%to collected%' OR al.body LIKE '%to delivered%')) as completed_at_from_logs"),
            ])
            ->whereIn('purchase_arrival_assignments.root_item_id', $rootItemIds->all())
            ->whereNull('purchase_arrival_assignments.undone_at')
            ->orderByDesc('purchase_arrival_assignments.matched_at')
            ->limit(80)
            ->get()
            ->map(function ($arrival) {
                $status = (string) ($arrival->status ?? '');

                if (empty($arrival->informed_at) && in_array($status, ['ready_for_collection', 'for_delivery'], true)) {
                    $arrival->informed_at = $arrival->status_updated_at ?: $arrival->matched_at;
                }

                $arrival->completed_at = $arrival->completed_at_from_logs;
                if (empty($arrival->completed_at) && in_array($status, ['collected', 'delivered'], true)) {
                    $arrival->completed_at = $arrival->status_updated_at ?: $arrival->matched_at;
                }

                $arrival->completion_label = $status === 'delivered' ? 'Delivered' : (in_array($status, ['collected', 'ready_for_collection', 'for_delivery'], true) ? 'Collected' : 'Completed');

                return $arrival;
            });
    }

    public function notes(object $order): Collection
    {
        return DB::table('activity_logs')
            ->select([
                'id',
                'subject_type',
                'subject_id',
                'type',
                'title',
                'body',
                'occurred_at',
                'created_at',
                'is_pinned',
            ])
            ->whereNull('deleted_at')
            ->where(function ($query) use ($order) {
                $query
                    ->where(function ($orderQuery) use ($order) {
                        $orderQuery
                            ->whereIn('subject_type', ['order', 'App\\Models\\Order'])
                            ->where('subject_id', $order->id);
                    })
                    ->orWhere(function ($draftQuery) use ($order) {
                        $draftQuery
                            ->where('subject_type', 'draft_order')
                            ->where('subject_id', $order->draft_order_id);
                    });
            })
            ->orderByDesc('is_pinned')
            ->orderByDesc(DB::raw('COALESCE(occurred_at, created_at)'))
            ->limit(40)
            ->get();
    }
    private function rootItemIdsForOrder(int $orderId): Collection
    {
        return DB::table('order_items')
            ->where('order_id', $orderId)
            ->selectRaw('COALESCE(root_item_id, id) as root_item_id')
            ->pluck('root_item_id')
            ->filter()
            ->unique()
            ->values();
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

        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }

        return $host;
    }

}