<?php

namespace App\Services\Drafts;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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

            if (! empty($draft->finalized_order_id)) {
                return (int) $draft->finalized_order_id;
            }

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
            $orderNumber = trim((string) ($draft->draft_number ?: $draft->id));
            $orderFeeRate = (float) ($draft->dabba_fee_rate ?? $customer->dabba_fee_rate ?? 20);
            if ($orderFeeRate > 1) {
                $orderFeeRate = round($orderFeeRate / 100, 4);
            }

            $orderId = (int) DB::table('orders')->insertGetId([
                'draft_order_id' => $draftId,
                'source_draft_order_id' => $draftId,
                'parent_order_id' => $draft->parent_order_id ?: null,
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
            $this->createOrderItems($orderId, $items, $retailerSummaries, $orderRetailerIds, $userId);

            DB::table('draft_orders')
                ->where('id', $draftId)
                ->update([
                    'state' => 'finalised',
                    'status' => 'finalised',
                    'finalized_order_id' => $orderId,
                    'updated_by_user_id' => $userId,
                    'updated_at' => now(),
                ]);

            DB::table('activity_logs')->insert([
                'subject_type' => 'draft_order',
                'subject_id' => $draftId,
                'type' => 'system_note',
                'title' => 'Draft finalised',
                'body' => 'Draft finalised into Order #' . $orderNumber . '.',
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
                'body' => 'Order created from Draft #' . $orderNumber . '.',
                'occurred_at' => now(),
                'created_by_user_id' => $userId,
                'updated_by_user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $orderId;
        });
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

    private function createOrderItems(int $orderId, Collection $items, Collection $retailerSummaries, array $orderRetailerIds, int $userId): void
    {
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

                DB::table('order_items')->insert([
                    'order_id' => $orderId,
                    'order_retailer_id' => $orderRetailerIds[(int) $retailerId] ?? null,
                    'source_order_item_id' => null,
                    'root_item_id' => null,
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
                    'status' => 'requested',
                    'last_status_changed_at' => now(),
                    'created_by_user_id' => $userId,
                    'updated_by_user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
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
