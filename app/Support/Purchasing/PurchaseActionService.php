<?php

namespace App\Support\Purchasing;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseActionService
{
    public function recordPurchase(array $data): int
    {
        $item = $this->findOrderItem((int) ($data['order_item_id'] ?? 0));
        $qty = $this->validPurchaseQty($item, (int) ($data['qty'] ?? 0));
        $unitPrice = $this->optionalMoney($data['purchase_unit_price'] ?? null);
        $lineTotal = $this->optionalMoney($data['purchase_line_total'] ?? null) ?? ($unitPrice !== null ? round($unitPrice * $qty, 2) : null);

        return DB::transaction(function () use ($data, $item, $qty, $unitPrice, $lineTotal) {
            $purchaseId = DB::table('order_item_purchases')->insertGetId([
                'order_item_id' => $item->id,
                'root_item_id' => $item->root_item_id ?: $item->id,
                'order_id' => $item->order_id,
                'order_retailer_id' => $item->order_retailer_id,
                'retailer_id' => $item->retailer_id,
                'qty' => $qty,
                'status' => 'purchased',
                'purchase_unit_price' => $unitPrice,
                'purchase_line_total' => $lineTotal,
                'currency' => strtoupper((string) ($data['currency'] ?? 'GBP')) ?: 'GBP',
                'marketplace_seller' => $this->blankToNull($data['marketplace_seller'] ?? null),
                'retailer_order_reference' => $this->blankToNull($data['retailer_order_reference'] ?? null),
                'note' => $this->blankToNull($data['note'] ?? null),
                'ordered_at' => $data['ordered_at'] ?? now(),
                'expected_dispatch_at' => $data['expected_dispatch_at'] ?? null,
                'expected_uk_hub_at' => $data['expected_uk_hub_at'] ?? null,
                'expected_gibraltar_at' => $data['expected_gibraltar_at'] ?? null,
                'requires_marking_attention' => ! empty($data['requires_marking_attention']),
                'internal_notes' => $this->blankToNull($data['internal_notes'] ?? null),
                'created_by_user_id' => $data['user_id'] ?? null,
                'updated_by_user_id' => $data['user_id'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->writeActivityLog('order_item_purchase', $purchaseId, 'Purchase recorded', 'Purchase recorded for ' . $qty . ' item(s).', $data['user_id'] ?? null);

            return $purchaseId;
        });
    }

    public function recordProblem(array $data): int
    {
        $item = $this->findOrderItem((int) ($data['order_item_id'] ?? 0));
        $qty = $this->validProblemQty($item, (int) ($data['qty'] ?? 0));

        return DB::transaction(function () use ($data, $item, $qty) {
            $purchaseId = DB::table('order_item_purchases')->insertGetId([
                'order_item_id' => $item->id,
                'root_item_id' => $item->root_item_id ?: $item->id,
                'order_id' => $item->order_id,
                'order_retailer_id' => $item->order_retailer_id,
                'retailer_id' => $item->retailer_id,
                'qty' => $qty,
                'status' => 'failed',
                'currency' => strtoupper((string) ($data['currency'] ?? 'GBP')) ?: 'GBP',
                'marketplace_seller' => $this->blankToNull($data['marketplace_seller'] ?? null),
                'retailer_order_reference' => $this->blankToNull($data['retailer_order_reference'] ?? null),
                'problem_code' => $this->blankToNull($data['problem_code'] ?? null),
                'problem_notes' => $this->blankToNull($data['problem_notes'] ?? null),
                'resolution_action' => $this->blankToNull($data['resolution_action'] ?? null),
                'resolution_status' => $data['resolution_status'] ?? 'pending',
                'created_by_user_id' => $data['user_id'] ?? null,
                'updated_by_user_id' => $data['user_id'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->writeActivityLog('order_item_purchase', $purchaseId, 'Purchase problem recorded', 'Purchase problem recorded for ' . $qty . ' item(s).', $data['user_id'] ?? null);

            return $purchaseId;
        });
    }

    public function undoPurchase(int $purchaseId, ?int $userId = null, ?string $reason = null): void
    {
        $purchase = DB::table('order_item_purchases')->where('id', $purchaseId)->first();

        if (! $purchase || $purchase->cancelled_at !== null) {
            throw ValidationException::withMessages(['purchase' => 'This purchase record cannot be undone.']);
        }

        DB::transaction(function () use ($purchase, $userId, $reason) {
            DB::table('order_item_purchases')
                ->where('id', $purchase->id)
                ->update([
                    'cancelled_at' => now(),
                    'requires_marking_attention' => 0,
                    'updated_by_user_id' => $userId,
                    'updated_at' => now(),
                    'internal_notes' => trim((string) $purchase->internal_notes . "\nUndo: " . ($reason ?: 'No reason recorded.')),
                ]);

            DB::table('order_items')
                ->where('id', $purchase->order_item_id)
                ->update([
                    'requires_inspection' => 0,
                    'inspection_note' => null,
                    'updated_by_user_id' => $userId,
                    'updated_at' => now(),
                ]);

            DB::table('order_item_purchases')->insert([
                'order_item_id' => $purchase->order_item_id,
                'root_item_id' => $purchase->root_item_id,
                'order_id' => $purchase->order_id,
                'order_retailer_id' => $purchase->order_retailer_id,
                'retailer_id' => $purchase->retailer_id,
                'qty' => $purchase->qty,
                'status' => 'reversed',
                'reversal_of_purchase_id' => $purchase->id,
                'currency' => $purchase->currency ?: 'GBP',
                'note' => $reason,
                'created_by_user_id' => $userId,
                'updated_by_user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->writeActivityLog('order_item_purchase', $purchase->id, 'Purchase undone', trim(($reason ?: 'Purchase event was undone.') . ' Package check marker was cleared automatically.'), $userId);
        });
    }

    public function undoProblem(int $purchaseId, ?int $userId = null, ?string $reason = null): void
    {
        $this->undoPurchase($purchaseId, $userId, $reason);
    }

    private function findOrderItem(int $orderItemId): object
    {
        $item = DB::table('order_items')
            ->leftJoin('order_retailers', 'order_retailers.id', '=', 'order_items.order_retailer_id')
            ->select([
                'order_items.id',
                'order_items.root_item_id',
                'order_items.order_id',
                'order_items.order_retailer_id',
                'order_items.quantity',
                'order_retailers.retailer_id',
            ])
            ->where('order_items.id', $orderItemId)
            ->first();

        if (! $item) {
            throw ValidationException::withMessages(['order_item_id' => 'Order item was not found.']);
        }

        return $item;
    }

    private function validPurchaseQty(object $item, int $qty): int
    {
        if ($qty < 1) {
            throw ValidationException::withMessages(['qty' => 'Quantity must be at least 1.']);
        }

        $totals = $this->itemOperationalTotals($item);

        if ($qty > $totals['purchase_remaining_qty']) {
            throw ValidationException::withMessages(['qty' => 'Quantity cannot be greater than the remaining quantity to buy for this item.']);
        }

        return $qty;
    }

    private function validProblemQty(object $item, int $qty): int
    {
        if ($qty < 1) {
            throw ValidationException::withMessages(['qty' => 'Quantity must be at least 1.']);
        }

        $totals = $this->itemOperationalTotals($item);
        $allowedQty = max($totals['purchase_remaining_qty'], $totals['arrival_remaining_qty']);

        if ($qty > $allowedQty) {
            throw ValidationException::withMessages(['qty' => 'Quantity cannot be greater than the quantity currently exposed to a purchasing problem.']);
        }

        return $qty;
    }

    private function itemOperationalTotals(object $item): array
    {
        $rootItemId = $item->root_item_id ?: $item->id;

        $purchasedQty = (int) DB::table('order_item_purchases')
            ->where('root_item_id', $rootItemId)
            ->whereIn('status', PurchaseProgressSummary::PURCHASED_STATUSES)
            ->whereNull('cancelled_at')
            ->sum('qty');

        $problemQty = (int) DB::table('order_item_purchases')
            ->where('root_item_id', $rootItemId)
            ->whereIn('status', PurchaseProgressSummary::PROBLEM_STATUSES)
            ->whereNull('cancelled_at')
            ->sum('qty');

        $arrivedQty = (int) DB::table('purchase_arrival_assignments')
            ->where('root_item_id', $rootItemId)
            ->whereNull('undone_at')
            ->sum('qty');

        return [
            'purchase_remaining_qty' => max(0, (int) $item->quantity - $purchasedQty - $problemQty),
            'arrival_remaining_qty' => max(0, $purchasedQty - $problemQty - $arrivedQty),
        ];
    }

    private function optionalMoney(mixed $amount): ?float
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        return round((float) $amount, 2);
    }

    private function blankToNull(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function writeActivityLog(string $subjectType, int $subjectId, string $title, string $body, ?int $userId): void
    {
        DB::table('activity_logs')->insert([
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'type' => 'system',
            'title' => $title,
            'body' => $body,
            'occurred_at' => now(),
            'created_by_user_id' => $userId,
            'updated_by_user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
