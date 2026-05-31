<?php

namespace App\Services\Drafts;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class FinaliseDraftOrderService
{
    public function __construct(private DraftOrderWorkspaceService $workspace)
    {
    }

    public function finalise(int $draftId, int $userId): int
    {
        return DB::transaction(function () use ($draftId, $userId): int {
            $draft = DB::table('draft_orders')
                ->where('id', $draftId)
                ->lockForUpdate()
                ->first();

            if (! $draft) {
                throw new RuntimeException('Draft order not found.');
            }

            $previousOrderId = $this->previousOrderId($draft);

            $items = DB::table('draft_order_items')
                ->where('draft_order_id', $draftId)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($items->isEmpty()) {
                throw new RuntimeException('Add at least one item before finalising this draft.');
            }

            $this->workspace->recalculate($draftId, $userId);

            $draft = DB::table('draft_orders')
                ->where('id', $draftId)
                ->lockForUpdate()
                ->first();

            $retailerSummaries = DB::table('draft_order_retailers')
                ->where('draft_order_id', $draftId)
                ->get()
                ->keyBy('retailer_id');

            $customer = DB::table('customers')->where('id', $draft->customer_id)->first();
            if (! $customer) {
                throw new RuntimeException('The draft customer could not be found.');
            }

            $billing = $this->billingSnapshot((int) $draft->customer_id, $customer);
            $orderNumber = $this->businessOrderNumber($draft, $previousOrderId);
            $orderFeeRate = (float) ($draft->dabba_fee_rate ?? $customer->dabba_fee_rate ?? 20);
            if ($orderFeeRate > 1) {
                $orderFeeRate = round($orderFeeRate / 100, 4);
            }

            $orderId = (int) DB::table('orders')->insertGetId([
                'draft_order_id' => $draftId,
                'source_draft_order_id' => $draftId,
                'parent_order_id' => $previousOrderId ?: ($draft->parent_order_id ?: null),
                'order_type' => 'invoice',
                'order_number' => $orderNumber,
                'status' => 'ready',
                'dabba_fee_level' => (string) ($draft->dabba_fee_level ?: $customer->dabba_fee_level ?: 'global'),
                'dabba_fee_rate' => $orderFeeRate,
                'dabba_fee_min' => (float) ($draft->dabba_fee_min ?? $customer->dabba_fee_min ?? 10),
                'fee_mode' => (string) ($draft->fee_mode ?: 'standard'),
                'subtotal' => (float) ($draft->items_subtotal ?? 0),
                'retailer_delivery_fee_total' => (float) ($draft->retailer_delivery_total ?? 0),
                'dabba_fee_amount' => (float) ($draft->dabba_fee_total ?? 0),
                'grand_total' => (float) ($draft->grand_total ?? 0),
                'bill_to_name' => $billing['name'],
                'bill_to_company' => $billing['company'],
                'bill_to_email' => $billing['email'],
                'bill_to_phone' => $billing['phone'],
                'bill_to_address_line1' => $billing['address_line1'],
                'bill_to_postcode' => $billing['postcode'],
                'bill_to_country_id' => $billing['country_id'],
                'locked_at' => now(),
                'created_by_user_id' => $userId,
                'updated_by_user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $orderRetailerIds = $this->createOrderRetailers($orderId, $retailerSummaries, $userId);
            $itemMap = $this->createOrderItems($orderId, $items, $retailerSummaries, $orderRetailerIds, $previousOrderId, $userId);

            if ($previousOrderId) {
                $this->carryForwardSettlementToNewRevision($previousOrderId, $orderId, (int) $draft->customer_id, $userId);

                DB::table('orders')
                    ->where('id', $previousOrderId)
                    ->whereNotIn('status', ['cancelled', 'superseded'])
                    ->update([
                        'status' => 'superseded',
                        'cancel_reason' => 'superseded',
                        'cancelled_at' => now(),
                        'updated_by_user_id' => $userId,
                        'updated_at' => now(),
                    ]);
            }

            DB::table('draft_orders')
                ->where('id', $draftId)
                ->update([
                    'state' => 'consumed',
                    'status' => 'consumed',
                    'parent_order_id' => $previousOrderId ?: ($draft->parent_order_id ?: null),
                    'finalized_order_id' => $orderId,
                    'updated_by_user_id' => $userId,
                    'updated_at' => now(),
                ]);

            DB::table('activity_logs')->insert([
                'subject_type' => 'draft_order',
                'subject_id' => $draftId,
                'type' => 'system_note',
                'title' => $previousOrderId ? 'New order version created' : 'Draft consumed',
                'body' => $previousOrderId
                    ? 'Draft was edited after prior consumption and used to create a new order version for Request #' . $orderNumber . '. Previous order ID ' . $previousOrderId . ' was marked as superseded. Any settled balance was moved through wallet credit and applied to the new revision where possible.'
                    : 'Draft consumed into Order/Request #' . $orderNumber . '.',
                'occurred_at' => now(),
                'created_by_user_id' => $userId,
                'updated_by_user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('activity_logs')->insert([
                'subject_type' => 'order',
                'subject_id' => $orderId,
                'type' => 'system_note',
                'title' => 'Order created',
                'body' => 'Order created from draft workspace for Request #' . $orderNumber . '.',
                'occurred_at' => now(),
                'created_by_user_id' => $userId,
                'updated_by_user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->copyCustomerRequestNotesToOrder($draftId, $orderId, $userId);

            return $orderId;
        });
    }


    private function businessOrderNumber(object $draft, ?int $previousOrderId): string
    {
        if ($previousOrderId) {
            $previousOrderNumber = DB::table('orders')->where('id', $previousOrderId)->value('order_number');
            if (trim((string) $previousOrderNumber) !== '') {
                return trim((string) $previousOrderNumber);
            }
        }

        if (! empty($draft->order_request_id)) {
            $requestRef = DB::table('order_requests')->where('id', $draft->order_request_id)->value('request_ref');
            if (trim((string) $requestRef) !== '') {
                return trim((string) $requestRef);
            }
        }

        return trim((string) ($draft->draft_number ?: $draft->id));
    }

    private function previousOrderId(object $draft): ?int
    {
        if (! empty($draft->finalized_order_id)) {
            return (int) $draft->finalized_order_id;
        }

        $row = DB::table('orders')
            ->where(function ($query) use ($draft) {
                $query->where('draft_order_id', $draft->id)
                    ->orWhere('source_draft_order_id', $draft->id);
            })
            ->orderByDesc('id')
            ->first(['id']);

        return $row ? (int) $row->id : null;
    }


    private function carryForwardSettlementToNewRevision(int $previousOrderId, int $newOrderId, int $customerId, int $userId): void
    {
        $previousSettled = $this->orderSettledTotal($previousOrderId);

        if ($previousSettled <= 0) {
            return;
        }

        $newOrder = DB::table('orders')
            ->where('id', $newOrderId)
            ->first(['id', 'grand_total', 'status']);

        if (! $newOrder) {
            return;
        }

        $creditId = $this->ensureSupersededOrderCredit($previousOrderId, $customerId, $previousSettled, $userId);

        $available = (float) DB::table('customer_credits')
            ->where('id', $creditId)
            ->value('remaining_amount');

        if ($available <= 0) {
            return;
        }

        $alreadyApplied = (float) DB::table('credit_applications')
            ->where('customer_credit_id', $creditId)
            ->where('order_id', $newOrderId)
            ->sum('amount_applied');

        $newTotal = max(0.0, round((float) ($newOrder->grand_total ?? 0), 2));
        $alreadySettled = $this->orderSettledTotal($newOrderId);
        $balanceDue = max(0.0, round($newTotal - $alreadySettled, 2));
        $amountToApply = max(0.0, min($available, $balanceDue));

        if ($amountToApply <= 0 || $alreadyApplied > 0) {
            $this->refreshOrderPaymentStatus($newOrderId, $userId);
            return;
        }

        $applicationId = (int) DB::table('credit_applications')->insertGetId([
            'customer_credit_id' => $creditId,
            'order_id' => $newOrderId,
            'invoice_version_id' => null,
            'amount_applied' => $amountToApply,
            'currency' => 'GBP',
            'applied_at' => now(),
            'applied_by_user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('order_transactions')->insert([
            'order_id' => $newOrderId,
            'invoice_version_id' => null,
            'payment_type_id' => null,
            'type' => 'credit_application',
            'amount' => $amountToApply,
            'currency' => 'GBP',
            'status' => 'recorded',
            'received_at' => now(),
            'method' => 'wallet',
            'channel' => 'internal',
            'provider' => 'DabbaDesk',
            'reference' => 'CA#' . $applicationId,
            'note' => 'Wallet credit carried forward from superseded Order ID #' . $previousOrderId . '.',
            'created_by_user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $newRemaining = max(0.0, round($available - $amountToApply, 2));
        DB::table('customer_credits')
            ->where('id', $creditId)
            ->update([
                'remaining_amount' => $newRemaining,
                'status' => $newRemaining > 0 ? 'open' : 'used',
                'updated_at' => now(),
            ]);

        $this->refreshOrderPaymentStatus($newOrderId, $userId);

        DB::table('activity_logs')->insert([
            'subject_type' => 'order',
            'subject_id' => $newOrderId,
            'type' => 'system_note',
            'title' => 'Superseded balance carried forward',
            'body' => '£' . number_format($amountToApply, 2) . ' was applied from wallet credit created from superseded Order ID #' . $previousOrderId . '.',
            'occurred_at' => now(),
            'created_by_user_id' => $userId,
            'updated_by_user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function ensureSupersededOrderCredit(int $previousOrderId, int $customerId, float $amount, int $userId): int
    {
        $existing = DB::table('customer_credits')
            ->where('source_type', 'superseded_order_balance')
            ->where('source_id', $previousOrderId)
            ->first(['id']);

        if ($existing) {
            return (int) $existing->id;
        }

        return (int) DB::table('customer_credits')->insertGetId([
            'customer_id' => $customerId,
            'order_id' => $previousOrderId,
            'source_type' => 'superseded_order_balance',
            'source_id' => $previousOrderId,
            'source_invoice_version_id' => null,
            'amount' => $amount,
            'remaining_amount' => $amount,
            'status' => 'open',
            'notes' => 'Settled balance moved from superseded order revision.',
            'currency' => 'GBP',
            'created_by_user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function orderSettledTotal(int $orderId): float
    {
        return round((float) DB::table('order_transactions')
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
            ->sum('amount'), 2);
    }

    private function refreshOrderPaymentStatus(int $orderId, int $userId): void
    {
        $order = DB::table('orders')->where('id', $orderId)->first(['id', 'grand_total', 'status']);

        if (! $order) {
            return;
        }

        $settled = $this->orderSettledTotal($orderId);
        $total = round((float) ($order->grand_total ?? 0), 2);

        if ($total > 0 && $settled + 0.01 >= $total) {
            DB::table('orders')->where('id', $orderId)->update([
                'status' => in_array((string) $order->status, ['ready', 'created'], true) ? 'paid' : $order->status,
                'paid_at' => now(),
                'updated_by_user_id' => $userId,
                'updated_at' => now(),
            ]);
        }
    }

    private function copyCustomerRequestNotesToOrder(int $draftId, int $orderId, int $userId): void
    {
        $notes = DB::table('activity_logs')
            ->where('subject_type', 'draft_order')
            ->where('subject_id', $draftId)
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query
                    ->where('type', 'order_request_note')
                    ->orWhere('title', 'Customer order request notes');
            })
            ->orderBy('id')
            ->get();

        foreach ($notes as $note) {
            $body = trim((string) ($note->body ?? ''));
            if ($body === '') {
                continue;
            }

            $alreadyCopied = DB::table('activity_logs')
                ->where('subject_type', 'order')
                ->where('subject_id', $orderId)
                ->where('type', 'order_request_note')
                ->where('body', $body)
                ->exists();

            if ($alreadyCopied) {
                continue;
            }

            DB::table('activity_logs')->insert([
                'subject_type' => 'order',
                'subject_id' => $orderId,
                'type' => 'order_request_note',
                'is_pinned' => 1,
                'title' => 'Customer order request notes',
                'body' => $body,
                'occurred_at' => $note->occurred_at ?: now(),
                'created_by_user_id' => $note->created_by_user_id ?: $userId,
                'updated_by_user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function createOrderRetailers(int $orderId, Collection $retailerSummaries, int $userId): array
    {
        $ids = [];

        foreach ($retailerSummaries as $summary) {
            $retailer = DB::table('retailers')->where('id', $summary->retailer_id)->first();

            $ids[(int) $summary->retailer_id] = (int) DB::table('order_retailers')->insertGetId([
                'order_id' => $orderId,
                'retailer_id' => (int) $summary->retailer_id,
                'retailer_name' => $retailer->name ?? null,
                'retailer_base_url' => $retailer->base_url ?? null,
                'retailer_items_subtotal' => (float) ($summary->retailer_subtotal ?? 0),
                'retailer_delivery_fee_total' => (float) ($summary->retailer_delivery_fee_total ?? 0),
                'dabba_fee_rate' => $summary->dabba_fee_rate,
                'dabba_fee_min' => $summary->dabba_fee_min,
                'dabba_fee_is_disabled' => (int) ($summary->dabba_fee_is_disabled ?? 0),
                'dabba_fee_reason' => $summary->dabba_fee_reason,
                'dabba_fee' => (float) ($summary->dabba_fee ?? 0),
                'status' => 'ready',
                'created_by_user_id' => $userId,
                'updated_by_user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $ids;
    }

    private function createOrderItems(int $orderId, Collection $items, Collection $retailerSummaries, array $orderRetailerIds, ?int $previousOrderId, int $userId): array
    {
        $previousItems = $this->previousOrderItems($previousOrderId);
        $usedPreviousItemIds = [];
        $itemMap = [];
        foreach ($items->groupBy('retailer_id') as $retailerId => $retailerItems) {
            $summary = $retailerSummaries->get($retailerId);
            $retailerSubtotal = max(0.0, (float) ($summary->retailer_subtotal ?? $retailerItems->sum('line_subtotal')));
            $retailerDeliveryTotal = (float) ($summary->retailer_delivery_fee_total ?? 0);
            $dabbaFeeTotal = (float) ($summary->dabba_fee ?? 0);

            $retailerDeliveryAllocations = $this->allocateMoney($retailerItems, $retailerDeliveryTotal, $retailerSubtotal);
            $dabbaFeeAllocations = $this->allocateMoney($retailerItems, $dabbaFeeTotal, $retailerSubtotal);

            foreach ($retailerItems->values() as $index => $item) {
                $qty = max(1, (int) ($item->qty ?? 1));
                $unit = round((float) ($item->unit_price ?? 0), 2);
                $lineSubtotal = round((float) ($item->line_subtotal ?? ($qty * $unit)), 2);
                $sellerDelivery = round((float) ($item->item_retailer_delivery_fee ?? $item->item_delivery_fee ?? 0), 2);
                $retailerDeliveryAllocated = $retailerDeliveryAllocations[$index] ?? 0.0;
                $dabbaFeeAllocated = $dabbaFeeAllocations[$index] ?? 0.0;
                $lineTotal = round($lineSubtotal + $sellerDelivery + $retailerDeliveryAllocated + $dabbaFeeAllocated, 2);
                $description = trim((string) ($item->description ?? ''));
                $itemName = $this->itemNameFromDescription($description, (string) ($item->product_code ?? ''));

                $previousItem = $this->matchPreviousOrderItem($item, $previousItems, $usedPreviousItemIds);
                $sourceOrderItemId = $previousItem ? (int) $previousItem->id : null;
                $rootItemId = $previousItem ? (int) ($previousItem->root_item_id ?: $previousItem->id) : null;

                $orderItemId = (int) DB::table('order_items')->insertGetId([
                    'order_id' => $orderId,
                    'order_retailer_id' => $orderRetailerIds[(int) $retailerId] ?? null,
                    'source_order_item_id' => $sourceOrderItemId,
                    'root_item_id' => $rootItemId,
                    'item_name' => $itemName,
                    'description' => $description ?: $itemName,
                    'product_code' => trim((string) ($item->product_code ?? $item->sku ?? '')) ?: null,
                    'product_url' => trim((string) ($item->url ?? '')) ?: null,
                    'marketplace_seller' => null,
                    'quantity' => $qty,
                    'unit_price' => $unit,
                    'line_subtotal' => $lineSubtotal,
                    'line_total' => $lineTotal,
                    'item_retailer_delivery_fee' => $sellerDelivery,
                    'retailer_delivery_allocated' => $retailerDeliveryAllocated,
                    'sort_order' => (int) ($item->sort_order ?? ($index + 1)),
                    'dabba_fee_allocated' => $dabbaFeeAllocated,
                    'status' => $previousItem ? (string) ($previousItem->status ?: 'requested') : 'requested',
                    'last_status_changed_at' => $previousItem && $previousItem->last_status_changed_at ? $previousItem->last_status_changed_at : now(),
                    'created_by_user_id' => $userId,
                    'updated_by_user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if (! $sourceOrderItemId) {
                    DB::table('order_items')->where('id', $orderItemId)->update(['root_item_id' => $orderItemId]);
                } else {
                    $itemMap[$sourceOrderItemId] = $orderItemId;
                }
            }
        }

        return $itemMap;
    }

    private function previousOrderItems(?int $previousOrderId): Collection
    {
        if (! $previousOrderId) {
            return collect();
        }

        return DB::table('order_items')
            ->where('order_id', $previousOrderId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    private function matchPreviousOrderItem(object $draftItem, Collection $previousItems, array &$usedPreviousItemIds): ?object
    {
        if ($previousItems->isEmpty()) {
            return null;
        }

        $sourceId = (int) ($draftItem->source_order_item_id ?? 0);
        if ($sourceId > 0) {
            $matched = $previousItems->first(fn ($item) => (int) $item->id === $sourceId && empty($usedPreviousItemIds[(int) $item->id]));
            if ($matched) {
                $usedPreviousItemIds[(int) $matched->id] = true;
                return $matched;
            }
        }

        $productCode = $this->normaliseMatchText((string) ($draftItem->product_code ?: $draftItem->sku ?: ''));
        if ($productCode !== '') {
            $matched = $previousItems->first(function ($item) use ($productCode, $usedPreviousItemIds) {
                return empty($usedPreviousItemIds[(int) $item->id])
                    && $this->normaliseMatchText((string) ($item->product_code ?? '')) === $productCode;
            });
            if ($matched) {
                $usedPreviousItemIds[(int) $matched->id] = true;
                return $matched;
            }
        }

        $url = $this->normaliseUrlForMatch((string) ($draftItem->url ?? ''));
        if ($url !== '') {
            $matched = $previousItems->first(function ($item) use ($url, $usedPreviousItemIds) {
                return empty($usedPreviousItemIds[(int) $item->id])
                    && $this->normaliseUrlForMatch((string) ($item->product_url ?? '')) === $url;
            });
            if ($matched) {
                $usedPreviousItemIds[(int) $matched->id] = true;
                return $matched;
            }
        }

        $description = $this->normaliseMatchText((string) ($draftItem->description ?? ''));
        if ($description !== '') {
            $matched = $previousItems->first(function ($item) use ($description, $usedPreviousItemIds) {
                return empty($usedPreviousItemIds[(int) $item->id])
                    && $this->normaliseMatchText((string) ($item->description ?? '')) === $description;
            });
            if ($matched) {
                $usedPreviousItemIds[(int) $matched->id] = true;
                return $matched;
            }
        }

        return null;
    }

    private function carryForwardOperationalState(int $previousOrderId, int $newOrderId, array $itemMap, int $userId): void
    {
        if (empty($itemMap)) {
            return;
        }

        $newItems = DB::table('order_items')
            ->whereIn('id', array_values($itemMap))
            ->get()
            ->keyBy('id');

        $purchaseMap = [];

        DB::table('order_item_purchases')
            ->where('order_id', $previousOrderId)
            ->whereIn('order_item_id', array_keys($itemMap))
            ->orderBy('id')
            ->get()
            ->each(function ($purchase) use ($newOrderId, $itemMap, $newItems, &$purchaseMap) {
                $newOrderItemId = $itemMap[(int) $purchase->order_item_id] ?? null;
                $newItem = $newOrderItemId ? $newItems->get($newOrderItemId) : null;

                if (! $newOrderItemId || ! $newItem) {
                    return;
                }

                $row = (array) $purchase;
                unset($row['id']);
                $row['order_id'] = $newOrderId;
                $row['order_item_id'] = $newOrderItemId;
                $row['order_retailer_id'] = $newItem->order_retailer_id ?? null;
                $row['root_item_id'] = $newItem->root_item_id ?: $newOrderItemId;

                $newPurchaseId = $this->insertFiltered('order_item_purchases', $row);
                $purchaseMap[(int) $purchase->id] = $newPurchaseId;
            });

        if (! empty($purchaseMap)) {
            DB::table('purchase_arrival_assignments')
                ->where('order_id', $previousOrderId)
                ->whereIn('order_item_purchase_id', array_keys($purchaseMap))
                ->orderBy('id')
                ->get()
                ->each(function ($assignment) use ($newOrderId, $itemMap, $purchaseMap, $newItems) {
                    $newOrderItemId = $itemMap[(int) $assignment->order_item_id] ?? null;
                    $newPurchaseId = $purchaseMap[(int) $assignment->order_item_purchase_id] ?? null;
                    $newItem = $newOrderItemId ? $newItems->get($newOrderItemId) : null;

                    if (! $newOrderItemId || ! $newPurchaseId || ! $newItem) {
                        return;
                    }

                    $row = (array) $assignment;
                    unset($row['id']);
                    $row['order_id'] = $newOrderId;
                    $row['order_item_id'] = $newOrderItemId;
                    $row['order_item_purchase_id'] = $newPurchaseId;
                    $row['root_item_id'] = $newItem->root_item_id ?: $newOrderItemId;

                    $this->insertFiltered('purchase_arrival_assignments', $row);
                });
        }

        DB::table('activity_logs')->insert([
            'subject_type' => 'order',
            'subject_id' => $newOrderId,
            'type' => 'system_note',
            'title' => 'Prior operational state carried forward',
            'body' => 'Purchases and arrivals were carried forward from superseded Order ID #' . $previousOrderId . '. Financial settlement was not copied; the new revision must have its own payment or wallet-credit application.',
            'occurred_at' => now(),
            'created_by_user_id' => $userId,
            'updated_by_user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertFiltered(string $table, array $row): int
    {
        $columns = Schema::getColumnListing($table);
        $filtered = array_intersect_key($row, array_flip($columns));

        return (int) DB::table($table)->insertGetId($filtered);
    }

    private function normaliseMatchText(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/\s+/', ' ', $value) ?: '';

        return $value;
    }

    private function normaliseUrlForMatch(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        $parts = parse_url($url);
        if (! $parts || empty($parts['host'])) {
            return mb_strtolower($url);
        }

        $host = preg_replace('/^www\./', '', mb_strtolower($parts['host']));
        $path = rtrim((string) ($parts['path'] ?? ''), '/');

        return $host . $path;
    }

    private function allocateMoney(Collection $items, float $amount, float $subtotal): array
    {
        $amount = round($amount, 2);
        $count = $items->count();

        if ($count === 0 || $amount <= 0) {
            return array_fill(0, $count, 0.0);
        }

        $allocations = [];
        $running = 0.0;
        $values = $items->values();

        foreach ($values as $index => $item) {
            if ($index === $count - 1) {
                $share = round($amount - $running, 2);
            } elseif ($subtotal > 0) {
                $share = round($amount * ((float) ($item->line_subtotal ?? 0) / $subtotal), 2);
            } else {
                $share = round($amount / $count, 2);
            }

            $allocations[$index] = $share;
            $running = round($running + $share, 2);
        }

        return $allocations;
    }

    private function billingSnapshot(int $customerId, object $customer): array
    {
        $name = trim(trim((string) ($customer->first_name ?? '')) . ' ' . trim((string) ($customer->last_name ?? '')));
        if ($name === '') {
            $name = trim((string) ($customer->company_name ?? '')) ?: 'Unknown customer';
        }

        $email = DB::table('customer_emails as ce')
            ->join('emails as e', 'e.id', '=', 'ce.email_id')
            ->where('ce.customer_id', $customerId)
            ->where('ce.is_active', 1)
            ->orderByDesc('ce.is_primary')
            ->value('e.email');

        $phone = DB::table('customer_phones as cp')
            ->join('phones as p', 'p.id', '=', 'cp.phone_id')
            ->where('cp.customer_id', $customerId)
            ->where('cp.is_active', 1)
            ->orderByDesc('cp.is_primary')
            ->value('p.phone');

        $address = DB::table('customer_addresses as ca')
            ->join('addresses as a', 'a.id', '=', 'ca.address_id')
            ->where('ca.customer_id', $customerId)
            ->where('ca.is_active', 1)
            ->orderByDesc('ca.is_primary')
            ->select('a.line1', 'a.line2', 'a.city', 'a.region', 'a.postcode', 'a.country_id')
            ->first();

        $addressParts = [];
        if ($address) {
            $addressParts = array_filter([
                $address->line1 ?? null,
                $address->line2 ?? null,
                $address->city ?? null,
                $address->region ?? null,
            ]);
        }

        return [
            'name' => $name,
            'company' => $customer->company_name ?? null,
            'email' => $email ?: null,
            'phone' => $phone ?: null,
            'address_line1' => $addressParts ? implode(', ', $addressParts) : null,
            'postcode' => $address->postcode ?? null,
            'country_id' => $address->country_id ?? null,
        ];
    }

    private function itemNameFromDescription(string $description, string $productCode): string
    {
        $firstLine = trim((string) strtok($description, "\n"));
        $name = $firstLine ?: trim($productCode) ?: 'Draft item';

        return mb_substr($name, 0, 191);
    }
}
