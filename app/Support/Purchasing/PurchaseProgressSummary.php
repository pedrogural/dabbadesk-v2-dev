<?php

namespace App\Support\Purchasing;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PurchaseProgressSummary
{
    public const PURCHASED_STATUSES = ['purchased', 'ordered', 'received', 'confirmed', 'dispatched', 'in_transit', 'arrived'];
    public const PROBLEM_STATUSES = ['failed', 'problem', 'supplier_problem', 'supplier_cancelled', 'cancelled', 'retailer_cancelled', 'refunded', 'retailer_refunded', 'unavailable', 'lost', 'damaged', 'wrong_item'];
    public const READY_STATUSES = ['ready_for_collection', 'for_delivery'];
    public const COMPLETED_STATUSES = ['collected', 'delivered'];

    public function forOrder(int $orderId): array
    {
        $itemQty = (int) DB::table('order_items')
            ->where('order_id', $orderId)
            ->sum('quantity');

        return $this->forRootItemIds($this->rootItemIdsForOrder($orderId), $itemQty);
    }

    public function forRootItemIds(Collection $rootItemIds, int $requestedQty): array
    {
        $rootItemIds = $rootItemIds->filter()->unique()->values();

        if ($rootItemIds->isEmpty()) {
            return $this->emptySummary($requestedQty);
        }

        $purchasedQty = (int) DB::table('order_item_purchases')
            ->whereIn('root_item_id', $rootItemIds->all())
            ->whereIn('status', self::PURCHASED_STATUSES)
            ->whereNull('cancelled_at')
            ->sum('qty');

        $problemQty = (int) DB::table('order_item_purchases')
            ->whereIn('root_item_id', $rootItemIds->all())
            ->whereIn('status', self::PROBLEM_STATUSES)
            ->whereNull('cancelled_at')
            ->sum('qty');

        $arrivedQty = (int) DB::table('purchase_arrival_assignments')
            ->whereIn('root_item_id', $rootItemIds->all())
            ->whereNull('undone_at')
            ->sum('qty');

        $readyQty = (int) DB::table('purchase_arrival_assignments')
            ->whereIn('root_item_id', $rootItemIds->all())
            ->whereNull('undone_at')
            ->whereIn('status', self::READY_STATUSES)
            ->sum('qty');

        $completedQty = (int) DB::table('purchase_arrival_assignments')
            ->whereIn('root_item_id', $rootItemIds->all())
            ->whereNull('undone_at')
            ->whereIn('status', self::COMPLETED_STATUSES)
            ->sum('qty');

        $purchasedQty = min($requestedQty, $purchasedQty);
        $problemQty = min($requestedQty, $problemQty);
        $arrivedQty = min($requestedQty, $arrivedQty);
        $readyQty = min($requestedQty, $readyQty);
        $completedQty = min($requestedQty, $completedQty);

        return [
            'item_qty' => $requestedQty,
            'requested_qty' => $requestedQty,
            'purchased_qty' => $purchasedQty,
            'problem_qty' => $problemQty,
            'remaining_purchase_qty' => max(0, $requestedQty - $purchasedQty - $problemQty),
            'arrived_qty' => $arrivedQty,
            'expected_arrival_qty' => max(0, $purchasedQty - $problemQty),
            'remaining_arrival_qty' => max(0, $purchasedQty - $problemQty - $arrivedQty),
            'ready_qty' => $readyQty,
            'collected_qty' => $completedQty,
            'completed_qty' => $completedQty,
        ];
    }

    public function rootItemIdsForOrder(int $orderId): Collection
    {
        return DB::table('order_items')
            ->where('order_id', $orderId)
            ->selectRaw('COALESCE(root_item_id, id) as root_item_id')
            ->pluck('root_item_id')
            ->filter()
            ->unique()
            ->values();
    }

    private function emptySummary(int $requestedQty): array
    {
        return [
            'item_qty' => $requestedQty,
            'requested_qty' => $requestedQty,
            'purchased_qty' => 0,
            'problem_qty' => 0,
            'remaining_purchase_qty' => $requestedQty,
            'arrived_qty' => 0,
            'remaining_arrival_qty' => 0,
            'ready_qty' => 0,
            'collected_qty' => 0,
            'completed_qty' => 0,
        ];
    }
}
