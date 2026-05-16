<?php

namespace App\Services\Orders;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrdersReadOnlyService
{
    public function statusOptions(): Collection
    {
        return DB::table('orders')
            ->select('status')
            ->whereNotNull('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');
    }

    public function search(array $filters): LengthAwarePaginator
    {
        $queryText = trim((string) ($filters['q'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));

        $settlementSubquery = DB::table('order_transactions')
            ->select('order_id', DB::raw('SUM(amount) as settled_total'))
            ->where('status', 'recorded')
            ->groupBy('order_id');

        $itemSubquery = DB::table('order_items')
            ->select('order_id', DB::raw('COUNT(*) as item_count'), DB::raw('SUM(quantity) as total_qty'))
            ->groupBy('order_id');

        $purchaseSubquery = DB::table('order_item_purchases')
            ->select('order_id', DB::raw('SUM(qty) as purchased_qty'))
            ->whereIn('status', ['purchased', 'ordered', 'received'])
            ->whereNull('cancelled_at')
            ->groupBy('order_id');

        $arrivalSubquery = DB::table('purchase_arrival_assignments')
            ->select('order_id', DB::raw('SUM(qty) as arrived_qty'))
            ->whereNull('undone_at')
            ->groupBy('order_id');

        return DB::table('orders')
            ->join('draft_orders', 'draft_orders.id', '=', 'orders.draft_order_id')
            ->join('customers', 'customers.id', '=', 'draft_orders.customer_id')
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
                'orders.order_number',
                'orders.status',
                'orders.grand_total',
                'orders.bill_to_name',
                'orders.bill_to_email',
                'orders.created_at',
                'orders.paid_at',
                'orders.purchased_at',
                'orders.completed_at',
                'customers.id as customer_id',
                'customers.first_name',
                'customers.last_name',
                'customers.company_name',
                DB::raw('COALESCE(item_totals.item_count, 0) as item_count'),
                DB::raw('COALESCE(item_totals.total_qty, 0) as total_qty'),
                DB::raw('COALESCE(purchase_totals.purchased_qty, 0) as purchased_qty'),
                DB::raw('COALESCE(arrival_totals.arrived_qty, 0) as arrived_qty'),
                DB::raw('COALESCE(settlement_totals.settled_total, 0) as settled_total'),
                DB::raw('GREATEST(orders.grand_total - COALESCE(settlement_totals.settled_total, 0), 0) as balance_due'),
            ])
            ->when($status !== '', function ($query) use ($status) {
                $query->where('orders.status', $status);
            })
            ->when($queryText !== '', function ($query) use ($queryText) {
                $query->where(function ($subQuery) use ($queryText) {
                    $subQuery
                        ->where('orders.order_number', 'like', "%{$queryText}%")
                        ->orWhere('orders.bill_to_name', 'like', "%{$queryText}%")
                        ->orWhere('orders.bill_to_email', 'like', "%{$queryText}%")
                        ->orWhere('customers.first_name', 'like', "%{$queryText}%")
                        ->orWhere('customers.last_name', 'like', "%{$queryText}%")
                        ->orWhere('customers.company_name', 'like', "%{$queryText}%")
                        ->orWhereExists(function ($itemQuery) use ($queryText) {
                            $itemQuery
                                ->select(DB::raw(1))
                                ->from('order_items')
                                ->whereColumn('order_items.order_id', 'orders.id')
                                ->where(function ($itemSearch) use ($queryText) {
                                    $itemSearch
                                        ->where('order_items.item_name', 'like', "%{$queryText}%")
                                        ->orWhere('order_items.product_code', 'like', "%{$queryText}%")
                                        ->orWhere('order_items.retailer_order_reference', 'like', "%{$queryText}%")
                                        ->orWhere('order_items.tracking_reference', 'like', "%{$queryText}%");
                                });
                        })
                        ->orWhereExists(function ($purchaseQuery) use ($queryText) {
                            $purchaseQuery
                                ->select(DB::raw(1))
                                ->from('order_item_purchases')
                                ->whereColumn('order_item_purchases.order_id', 'orders.id')
                                ->where(function ($purchaseSearch) use ($queryText) {
                                    $purchaseSearch
                                        ->where('order_item_purchases.retailer_order_reference', 'like', "%{$queryText}%")
                                        ->orWhere('order_item_purchases.marketplace_seller', 'like', "%{$queryText}%");
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
                'orders.order_type',
                'orders.order_number',
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

        $purchasedQty = (int) DB::table('order_item_purchases')
            ->where('order_id', $orderId)
            ->whereIn('status', ['purchased', 'ordered', 'received'])
            ->whereNull('cancelled_at')
            ->sum('qty');

        $arrivedQty = (int) DB::table('purchase_arrival_assignments')
            ->where('order_id', $orderId)
            ->whereNull('undone_at')
            ->sum('qty');

        $readyQty = (int) DB::table('purchase_arrival_assignments')
            ->where('order_id', $orderId)
            ->whereNull('undone_at')
            ->whereIn('status', ['ready_for_collection', 'for_delivery'])
            ->sum('qty');

        $collectedQty = (int) DB::table('purchase_arrival_assignments')
            ->where('order_id', $orderId)
            ->whereNull('undone_at')
            ->whereIn('status', ['collected', 'delivered'])
            ->sum('qty');

        return [
            'item_qty' => $itemQty,
            'purchased_qty' => $purchasedQty,
            'arrived_qty' => $arrivedQty,
            'ready_qty' => $readyQty,
            'collected_qty' => $collectedQty,
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
                'order_item_id',
                DB::raw('SUM(qty) as purchased_qty'),
                DB::raw('MAX(retailer_order_reference) as latest_retailer_order_reference'),
                DB::raw('MAX(expected_uk_hub_at) as latest_expected_uk_hub_at'),
                DB::raw('MAX(marketplace_seller) as latest_marketplace_seller')
            )
            ->whereNull('cancelled_at')
            ->groupBy('order_item_id');

        $arrivalSubquery = DB::table('purchase_arrival_assignments')
            ->select(
                'order_item_id',
                DB::raw('SUM(qty) as arrived_qty'),
                DB::raw('MAX(status) as latest_arrival_status'),
                DB::raw('MAX(matched_at) as latest_matched_at')
            )
            ->whereNull('undone_at')
            ->groupBy('order_item_id');

        return DB::table('order_items')
            ->leftJoinSub($purchaseSubquery, 'purchase_totals', function ($join) {
                $join->on('purchase_totals.order_item_id', '=', 'order_items.id');
            })
            ->leftJoinSub($arrivalSubquery, 'arrival_totals', function ($join) {
                $join->on('arrival_totals.order_item_id', '=', 'order_items.id');
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
                $host = $this->hostFromUrl((string) $item->product_url);
                $seller = trim((string) ($item->latest_marketplace_seller ?: $item->marketplace_seller));

                $item->retailer_host = $host ?: 'Unknown retailer';
                $item->retailer_display_name = $seller ?: ($host ?: 'Unknown retailer');
                $item->retailer_group_key = Str::slug($item->retailer_display_name ?: 'unknown-retailer');
                $item->purchase_remaining_qty = max(0, (int) $item->quantity - (int) $item->purchased_qty);
                $item->arrival_remaining_qty = max(0, (int) $item->quantity - (int) $item->arrived_qty);

                return $item;
            });
    }

    public function purchases(int $orderId): Collection
    {
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
            ->where('order_item_purchases.order_id', $orderId)
            ->orderByDesc('order_item_purchases.created_at')
            ->limit(80)
            ->get();
    }

    public function arrivals(int $orderId): Collection
    {
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
            ])
            ->where('purchase_arrival_assignments.order_id', $orderId)
            ->orderByDesc('purchase_arrival_assignments.matched_at')
            ->limit(80)
            ->get();
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