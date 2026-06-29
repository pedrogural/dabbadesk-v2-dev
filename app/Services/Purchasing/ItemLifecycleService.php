<?php

namespace App\Services\Purchasing;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ItemLifecycleService
{
    public const PURCHASED_STATUSES = ['purchased', 'ordered', 'received'];

    public const PROBLEM_PURCHASE_STATUSES = [
        'unfulfilled',
        'failed',
        'problem',
        'supplier_problem',
        'supplier_cancelled',
        'cancelled',
        'refunded',
        'retailer_refunded',
        'lost',
        'damaged',
        'wrong_item',
        'unavailable',
    ];

    public const ACTIVE_ISSUE_STATUSES = ['open', 'awaiting_customer'];

    public const TERMINAL_ITEM_STATUSES = [
        'cancelled',
        'refunded',
        'deleted',
        'removed',
        'void',
        'voided',
        'superseded',
        'customer_cancelled',
        'cancelled_by_customer',
        'no_longer_required',
        'credited',
        'wallet_credited',
    ];

    public const NON_LIVE_ORDER_STATUSES = [
        'cancelled',
        'refunded',
        'superseded',
    ];

    public const RETURN_TO_BUY_STATUSES = ['returned_to_buy', 'resolved'];

    public const TERMINAL_ISSUE_RESOLUTIONS = [
        'customer_cancelled',
        'customer_refunded',
        'duplicate_item',
        'no_longer_required',
    ];


    /**
     * Apply the one shared definition of an order that may still be worked in Purchases.
     *
     * A superseded/cancelled/refunded order can remain viewable elsewhere as history,
     * but Purchases must only operate on the current live order version.
     */
    public function applyLivePurchasingOrderConstraints(Builder $query, string $orderAlias = 'orders'): Builder
    {
        return $query
            ->whereNotIn($orderAlias . '.status', self::NON_LIVE_ORDER_STATUSES)
            ->whereNull($orderAlias . '.cancelled_at')
            ->whereNotExists(function ($child) use ($orderAlias) {
                $child->from('orders as newer')
                    ->whereColumn('newer.parent_order_id', $orderAlias . '.id')
                    ->whereNotIn('newer.status', self::NON_LIVE_ORDER_STATUSES)
                    ->whereNull('newer.cancelled_at');
            });
    }

    public function isLivePurchasingOrder(int $orderId): bool
    {
        return $this->applyLivePurchasingOrderConstraints(
            DB::table('orders as o')->where('o.id', $orderId),
            'o'
        )->exists();
    }

    /**
     * Apply the one shared definition of an order item that still exists for purchasing.
     *
     * If an item has been removed/cancelled/refunded/credited as part of an order
     * amendment, it must not remain in the buying list even if it has no purchase event.
     */
    public function applyPurchasableItemConstraints(Builder $query, string $itemAlias = 'order_items'): Builder
    {
        return $query
            ->whereNotIn($itemAlias . '.status', self::TERMINAL_ITEM_STATUSES)
            ->where($itemAlias . '.quantity', '>', 0);
    }

    public function activeItemQtyExpression(string $itemAlias = 'order_items'): string
    {
        $quoted = collect(self::TERMINAL_ITEM_STATUSES)
            ->map(fn (string $status) => "'" . str_replace("'", "''", $status) . "'")
            ->implode(',');

        return "CASE WHEN {$itemAlias}.status IN ({$quoted}) THEN 0 ELSE {$itemAlias}.quantity END";
    }

    /**
     * Aggregates purchase events to one row per root item.
     *
     * Important: a purchase row that was later undone is ignored via cancelled_at.
     * Historical problem rows only count as active blockers while still pending and
     * not returned to the buy queue.
     */
    public function purchaseTotalsSubquery(): Builder
    {
        return DB::table('order_item_purchases as oip')
            ->selectRaw('oip.root_item_id')
            ->selectRaw("SUM(CASE WHEN oip.status IN ('purchased','ordered','received') AND oip.cancelled_at IS NULL THEN oip.qty ELSE 0 END) as gross_purchased_qty")
            ->selectRaw("SUM(CASE WHEN oip.status IN ('unfulfilled','failed','problem','supplier_problem','supplier_cancelled','cancelled','refunded','retailer_refunded','lost','damaged','wrong_item','unavailable') AND oip.cancelled_at IS NULL AND COALESCE(oip.resolution_status, 'pending') = 'pending' AND COALESCE(oip.resolution_action, '') <> 'return_to_buy' THEN oip.qty ELSE 0 END) as terminal_problem_qty")
            ->selectRaw("SUM(CASE WHEN oip.status IN ('unfulfilled','failed','problem','supplier_problem','supplier_cancelled','cancelled','refunded','retailer_refunded','lost','damaged','wrong_item','unavailable') AND oip.cancelled_at IS NULL AND COALESCE(oip.resolution_status, 'pending') = 'pending' AND COALESCE(oip.resolution_action, '') <> 'return_to_buy' THEN oip.qty ELSE 0 END) as pending_problem_qty")
            ->selectRaw('MAX(oip.created_at) as latest_purchase_event_at')
            ->selectRaw('COUNT(*) as purchase_event_count')
            ->groupBy('oip.root_item_id');
    }

    /**
     * Aggregates arrivals to one row per root item.
     */
    public function arrivalTotalsSubquery(): Builder
    {
        return DB::table('purchase_arrival_assignments as paa')
            ->selectRaw('paa.root_item_id')
            ->selectRaw('SUM(CASE WHEN paa.undone_at IS NULL THEN paa.qty ELSE 0 END) as arrived_qty')
            ->selectRaw('MAX(paa.matched_at) as latest_arrival_at')
            ->groupBy('paa.root_item_id');
    }

    /**
     * Aggregates purchase issue workflow state to one row per root item.
     *
     * History stays in purchase_issues forever. This aggregate answers only the
     * operational question: what is currently active, terminal, or returned to buy?
     */
    public function issueTotalsSubquery(): Builder
    {
        return DB::table('purchase_issues as pi')
            ->selectRaw('pi.root_item_id')
            ->selectRaw("SUM(CASE WHEN pi.status IN ('open','awaiting_customer') AND COALESCE(pi.resolution_type, '') <> 'return_to_buy' THEN COALESCE(pi.affected_qty, pi.qty, 1) ELSE 0 END) as active_issue_qty")
            ->selectRaw("SUM(CASE WHEN pi.status IN ('open','awaiting_customer') AND COALESCE(pi.issue_stage, 'pre_purchase') = 'pre_purchase' AND COALESCE(pi.resolution_type, '') <> 'return_to_buy' THEN COALESCE(pi.affected_qty, pi.qty, 1) ELSE 0 END) as active_pre_purchase_issue_qty")
            ->selectRaw("SUM(CASE WHEN pi.status IN ('open','awaiting_customer') AND COALESCE(pi.issue_stage, 'pre_purchase') IN ('post_purchase','arrival') AND COALESCE(pi.resolution_type, '') <> 'return_to_buy' THEN COALESCE(pi.affected_qty, pi.qty, 1) ELSE 0 END) as active_post_purchase_issue_qty")
            ->selectRaw("SUM(CASE WHEN pi.status IN ('returned_to_buy','resolved') AND COALESCE(pi.resolution_type, '') = 'return_to_buy' THEN COALESCE(pi.affected_qty, pi.qty, 1) ELSE 0 END) as return_to_buy_issue_qty")
            ->selectRaw("SUM(CASE WHEN pi.status = 'resolved' AND pi.resolution_type IN ('customer_cancelled','customer_refunded','duplicate_item','no_longer_required') THEN COALESCE(pi.affected_qty, pi.qty, 1) ELSE 0 END) as resolved_terminal_issue_qty")
            ->selectRaw("SUM(CASE WHEN pi.status = 'awaiting_customer' AND COALESCE(pi.resolution_type, '') <> 'return_to_buy' THEN 1 ELSE 0 END) as awaiting_customer_issue_count")
            ->selectRaw('COUNT(*) as issue_history_count')
            ->selectRaw('MAX(pi.created_at) as latest_issue_at')
            ->groupBy('pi.root_item_id');
    }

    public function currentStateForItem(int $rootItemId, int $itemQty): array
    {
        $purchase = DB::query()->fromSub($this->purchaseTotalsSubquery(), 'pt')
            ->where('pt.root_item_id', $rootItemId)
            ->first();

        $arrival = DB::query()->fromSub($this->arrivalTotalsSubquery(), 'at')
            ->where('at.root_item_id', $rootItemId)
            ->first();

        $issue = DB::query()->fromSub($this->issueTotalsSubquery(), 'pit')
            ->where('pit.root_item_id', $rootItemId)
            ->first();

        $grossPurchasedQty = (int) ($purchase->gross_purchased_qty ?? 0);
        $returnToBuyQty = (int) ($issue->return_to_buy_issue_qty ?? 0);
        $purchasedQty = max(0, $grossPurchasedQty - $returnToBuyQty);
        $terminalProblemQty = (int) ($purchase->terminal_problem_qty ?? 0);
        $activeIssueQty = (int) ($issue->active_issue_qty ?? 0);
        $resolvedTerminalIssueQty = (int) ($issue->resolved_terminal_issue_qty ?? 0);
        $arrivedQty = (int) ($arrival->arrived_qty ?? 0);

        $remainingToBuyQty = min($itemQty, max(0, $itemQty - $purchasedQty - $terminalProblemQty - $activeIssueQty - $resolvedTerminalIssueQty));
        $awaitingArrivalQty = max(0, $purchasedQty - $terminalProblemQty - $arrivedQty);

        return [
            'root_item_id' => $rootItemId,
            'quantity' => $itemQty,
            'gross_purchased_qty' => $grossPurchasedQty,
            'return_to_buy_issue_qty' => $returnToBuyQty,
            'purchased_qty' => $purchasedQty,
            'terminal_problem_qty' => $terminalProblemQty,
            'pending_problem_qty' => (int) ($purchase->pending_problem_qty ?? 0),
            'active_issue_qty' => $activeIssueQty,
            'active_pre_purchase_issue_qty' => (int) ($issue->active_pre_purchase_issue_qty ?? 0),
            'active_post_purchase_issue_qty' => (int) ($issue->active_post_purchase_issue_qty ?? 0),
            'resolved_terminal_issue_qty' => $resolvedTerminalIssueQty,
            'awaiting_customer_issue_count' => (int) ($issue->awaiting_customer_issue_count ?? 0),
            'arrived_qty' => $arrivedQty,
            'remaining_to_buy_qty' => $remainingToBuyQty,
            'awaiting_arrival_qty' => $awaitingArrivalQty,
        ];
    }

    public function effectivePurchasedQty(int $rootItemId): int
    {
        return (int) $this->currentStateForItem($rootItemId, PHP_INT_MAX)['purchased_qty'];
    }

    public function remainingToBuyQty(int $rootItemId, int $itemQty): int
    {
        return (int) $this->currentStateForItem($rootItemId, $itemQty)['remaining_to_buy_qty'];
    }

    public function activePurchaseIssues(int $rootItemId)
    {
        return DB::table('purchase_issues')
            ->where('root_item_id', $rootItemId)
            ->whereIn('status', self::ACTIVE_ISSUE_STATUSES)
            ->where(function ($query) {
                $query->whereNull('resolution_type')
                    ->orWhere('resolution_type', '<>', 'return_to_buy');
            })
            ->orderByDesc('created_at')
            ->get();
    }
}
