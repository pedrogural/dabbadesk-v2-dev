<?php

namespace App\Services\OrderRequests;

use App\Services\Drafts\DraftOrderWorkspaceService;
use App\Services\Intake\FeePolicyLookupService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ConvertOrderRequestService
{
    public function convert(int $orderRequestId, string $customerMode, ?int $selectedCustomerId, array $customerPayload, int $userId, string $existingCustomerAction = 'keep', bool $allowEmptyBasket = false): int
    {
        return DB::transaction(function () use ($orderRequestId, $customerMode, $selectedCustomerId, $customerPayload, $userId, $existingCustomerAction, $allowEmptyBasket): int {
            $request = DB::table('order_requests')
                ->where('id', $orderRequestId)
                ->lockForUpdate()
                ->first();

            if (! $request) {
                throw new RuntimeException('Order request not found.');
            }

            if ($request->converted_at && $request->converted_draft_order_id) {
                return (int) $request->converted_draft_order_id;
            }

            $items = DB::table('order_request_items')
                ->where('order_request_id', $orderRequestId)
                ->whereNull('deleted_at')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            if ($items->isEmpty() && ! $allowEmptyBasket) {
                throw new RuntimeException('This request has no items to convert.');
            }

            $customerId = $customerMode === 'existing'
                ? $this->updateExistingCustomerForConversion((int) $selectedCustomerId, $customerPayload, $userId, $existingCustomerAction)
                : $this->createCustomerFromPayload($customerPayload, $userId);

            if (! DB::table('customers')->where('id', $customerId)->exists()) {
                throw new RuntimeException('Selected customer could not be found.');
            }

            $feePolicy = $this->feePolicyForCustomer($customerId);

            $draftId = DB::table('draft_orders')->insertGetId([
                'order_request_id' => $request->id,
                'customer_id' => $customerId,
                'draft_number' => (string) $request->request_ref,
                'kind' => 'normal',
                'state' => 'draft',
                'status' => 'open',
                'home_delivery_requested' => 0,
                'created_by_user_id' => $userId,
                'updated_by_user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
                'fee_mode' => $feePolicy['fee_mode'],
                'dabba_fee_level' => $feePolicy['level'],
                'dabba_fee_rate' => $feePolicy['rate_percent'],
                'dabba_fee_min' => $feePolicy['minimum_fee'],
            ]);

            foreach ($items as $index => $item) {
                $retailerId = $this->resolveRetailerId($item, $userId);
                $qty = max(1, (int) $item->quantity);
                $unitPrice = round((float) $item->unit_price, 2);
                // Important: order_request_items.line_total is not trusted as the goods subtotal.
                // In the public intake flow it may represent a displayed/estimated line total, and
                // older rows can contain already-calculated fee-style values. The draft workbench
                // must start from pure goods value only, then calculate retailer delivery and
                // Dabba fee as separate financial buckets.
                $lineSubtotal = round($qty * $unitPrice, 2);

                $description = (string) $item->description;
                $notes = trim((string) ($item->notes ?? ''));
                if ($notes !== '') {
                    $description .= "\n\n[Customer notes]\n" . $notes;
                }

                DB::table('draft_order_items')->insert([
                    'draft_order_id' => $draftId,
                    'source_order_item_id' => null,
                    'retailer_id' => $retailerId,
                    'product_code' => $item->product_code,
                    'description' => $description,
                    'url' => $item->retailer_url,
                    'sku' => $item->product_code,
                    'qty' => $qty,
                    'unit_price' => $unitPrice,
                    'line_subtotal' => $lineSubtotal,
                    'line_total' => $lineSubtotal,
                    'item_retailer_delivery_fee' => 0,
                    'item_delivery_fee' => 0,
                    'sort_order' => $index + 1,
                    'created_by_user_id' => $userId,
                    'updated_by_user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->recalculateDraftTotals($draftId, $userId);
            $this->copyRequestNoteToDraft($request, $draftId, $userId);

            DB::table('order_requests')
                ->where('id', $request->id)
                ->update([
                    'status' => 'converted',
                    'reviewed_at' => $request->reviewed_at ?: now(),
                    'reviewed_by_user_id' => $request->reviewed_by_user_id ?: $userId,
                    'converted_at' => now(),
                    'converted_by_user_id' => $userId,
                    'converted_draft_order_id' => $draftId,
                    'updated_at' => now(),
                ]);

            return (int) $draftId;
        });
    }

    private function updateExistingCustomerForConversion(int $customerId, array $payload, int $userId, string $existingCustomerAction = 'keep'): int
    {
        if ($customerId <= 0) {
            throw new RuntimeException('Choose an existing customer before converting.');
        }

        if (! DB::table('customers')->where('id', $customerId)->exists()) {
            throw new RuntimeException('Selected customer could not be found.');
        }

        if ($existingCustomerAction !== 'update') {
            return $customerId;
        }

        $normal = $this->normaliseCustomerPayload($payload);

        DB::table('customers')->where('id', $customerId)->update([
            'first_name' => $normal['first_name'],
            'last_name' => $normal['last_name'],
            'company_name' => $normal['company_name'],
            'customer_type' => $normal['company_name'] && ! $normal['first_name'] ? 'company' : 'individual',
            'updated_by_user_id' => $userId,
            'updated_at' => now(),
        ]);

        $this->attachEmail($customerId, $normal['email'], $userId);
        $this->attachPhone($customerId, $normal['phone_digits'], $normal['phone_country_id'], $userId);
        $this->attachPrimaryAddress($customerId, $normal['address_line1'], $normal['address_postcode'], $normal['address_country_id'], $userId, 'Updated during request conversion');

        return $customerId;
    }

    private function createCustomerFromPayload(array $payload, int $userId): int
    {
        $normal = $this->normaliseCustomerPayload($payload);

        if ($normal['first_name'] === '' && $normal['last_name'] === '' && $normal['company_name'] === '') {
            throw new RuntimeException('Add at least a customer name or company name before creating a new customer.');
        }

        $customerId = DB::table('customers')->insertGetId([
            'first_name' => $normal['first_name'] ?: null,
            'last_name' => $normal['last_name'] ?: null,
            'company_name' => $normal['company_name'] ?: null,
            'customer_type' => $normal['company_name'] && ! $normal['first_name'] ? 'company' : 'individual',
            'is_active' => 1,
            'created_by_user_id' => $userId,
            'updated_by_user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->attachEmail($customerId, $normal['email'], $userId);
        $this->attachPhone($customerId, $normal['phone_digits'], $normal['phone_country_id'], $userId);
        $this->attachPrimaryAddress($customerId, $normal['address_line1'], $normal['address_postcode'], $normal['address_country_id'], $userId, 'Order request');

        return (int) $customerId;
    }

    private function normaliseCustomerPayload(array $payload): array
    {
        return [
            'first_name' => Str::title(trim((string) ($payload['first_name'] ?? ''))),
            'last_name' => Str::title(trim((string) ($payload['last_name'] ?? ''))),
            'company_name' => trim((string) ($payload['company_name'] ?? '')) ?: null,
            'email' => strtolower(trim((string) ($payload['email'] ?? ''))),
            'phone_digits' => preg_replace('/\D+/', '', (string) ($payload['phone_digits'] ?? '')) ?: '',
            'phone_country_id' => ! empty($payload['phone_country_id']) ? (int) $payload['phone_country_id'] : null,
            'address_line1' => trim((string) ($payload['address_line1'] ?? '')),
            'address_postcode' => trim((string) ($payload['address_postcode'] ?? '')) ?: null,
            'address_country_id' => ! empty($payload['address_country_id']) ? (int) $payload['address_country_id'] : null,
        ];
    }

    private function attachEmail(int $customerId, string $email, int $userId): void
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return;
        }

        DB::table('customer_emails')->where('customer_id', $customerId)->update(['is_primary' => 0, 'updated_at' => now()]);

        $emailId = DB::table('emails')->where('email', $email)->value('id');
        if (! $emailId) {
            $emailId = DB::table('emails')->insertGetId([
                'email' => $email,
                'is_active' => 1,
                'created_by_user_id' => $userId,
                'updated_by_user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('customer_emails')->updateOrInsert(
            ['customer_id' => $customerId, 'email_id' => $emailId],
            [
                'is_primary' => 1,
                'is_active' => 1,
                'created_by_user_id' => $userId,
                'updated_by_user_id' => $userId,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    private function attachPhone(int $customerId, string $digits, ?int $countryId, int $userId): void
    {
        $digits = preg_replace('/\D+/', '', $digits) ?: '';
        if ($digits === '') {
            return;
        }

        DB::table('customer_phones')->where('customer_id', $customerId)->update(['is_primary' => 0, 'updated_at' => now()]);

        $phoneId = DB::table('phones')
            ->where('phone', $digits)
            ->where(function ($query) use ($countryId) {
                $countryId ? $query->where('country_id', $countryId) : $query->whereNull('country_id');
            })
            ->value('id');

        if (! $phoneId) {
            $phoneId = DB::table('phones')->insertGetId([
                'phone' => $digits,
                'country_id' => $countryId,
                'is_active' => 1,
                'created_by_user_id' => $userId,
                'updated_by_user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('customer_phones')->updateOrInsert(
            ['customer_id' => $customerId, 'phone_id' => $phoneId],
            [
                'is_primary' => 1,
                'is_active' => 1,
                'created_by_user_id' => $userId,
                'updated_by_user_id' => $userId,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    private function attachPrimaryAddress(int $customerId, string $line1, ?string $postcode, ?int $countryId, int $userId, string $label): void
    {
        $line1 = trim($line1);
        if ($line1 === '') {
            return;
        }

        DB::table('customer_addresses')->where('customer_id', $customerId)->update(['is_primary' => 0, 'updated_at' => now()]);

        $addressId = DB::table('addresses')->insertGetId([
            'line1' => $line1,
            'postcode' => $postcode,
            'country_id' => $countryId,
            'is_active' => 1,
            'created_by_user_id' => $userId,
            'updated_by_user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('customer_addresses')->insert([
            'customer_id' => $customerId,
            'address_id' => $addressId,
            'is_primary' => 1,
            'is_active' => 1,
            'label' => $label,
            'created_by_user_id' => $userId,
            'updated_by_user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function resolveRetailerId(object $item, int $userId): int
    {
        if ($item->retailer_id && DB::table('retailers')->where('id', $item->retailer_id)->exists()) {
            return (int) $item->retailer_id;
        }

        $host = $this->hostFromUrl((string) $item->retailer_url);

        if ($host !== '') {
            $existing = DB::table('retailers')
                ->whereRaw('LOWER(TRIM(base_url)) = ?', [$host])
                ->value('id');

            if ($existing) {
                DB::table('order_request_items')
                    ->where('id', $item->id)
                    ->update([
                        'retailer_id' => (int) $existing,
                        'updated_at' => now(),
                    ]);

                return (int) $existing;
            }
        }

        $label = trim((string) ($item->retailer_name ?: $host ?: 'unknown retailer'));

        throw new RuntimeException(
            'Cannot convert this request yet. Add the unknown retailer "' . $label . '" before creating the draft.'
        );
    }

    private function recalculateDraftTotals(int $draftId, int $userId): void
    {
        app(DraftOrderWorkspaceService::class)->recalculate($draftId, $userId);
    }

    private function feePolicyForCustomer(int $customerId): array
    {
        try {
            $policy = app(FeePolicyLookupService::class)->policyForCustomer($customerId);
        } catch (\Throwable) {
            $policy = [];
        }

        // Important: custom customer fees may legitimately be 0% with a fixed
        // minimum fee, for example "0% / £21 minimum". The previous fallback
        // logic treated a 0 rate as missing and silently reverted to 20%, which
        // made request → draft conversion ignore customer-level fees.
        $hasRate = array_key_exists('percentage_rate', $policy) || array_key_exists('percentage_rate_percent', $policy);

        if (array_key_exists('percentage_rate_percent', $policy)) {
            $ratePercent = (float) $policy['percentage_rate_percent'];
        } elseif ($hasRate) {
            $rawRate = (float) $policy['percentage_rate'];
            $ratePercent = $rawRate > 1 ? $rawRate : $rawRate * 100;
        } else {
            $ratePercent = 20.0;
        }

        $minimumFee = array_key_exists('minimum_fee', $policy)
            ? (float) $policy['minimum_fee']
            : 10.0;

        return [
            'rate_percent' => round(max(0, $ratePercent), 4),
            'minimum_fee' => round(max(0, $minimumFee), 2),
            'level' => (string) ($policy['level'] ?? 'global'),
            'fee_mode' => (string) ($policy['fee_mode'] ?? 'standard'),
        ];
    }

    private function copyRequestNoteToDraft(object $request, int $draftId, int $userId): void
    {
        $bodyParts = [];
        if (trim((string) $request->notes) !== '') {
            $bodyParts[] = trim((string) $request->notes);
        }
        $bodyParts[] = 'Converted from order request ' . $request->request_ref . '.';

        DB::table('activity_logs')->insert([
            'subject_type' => 'draft_order',
            'subject_id' => $draftId,
            'type' => 'note',
            'is_pinned' => 0,
            'title' => 'Order request converted',
            'body' => implode("\n\n", $bodyParts),
            'occurred_at' => now(),
            'created_by_user_id' => $userId,
            'updated_by_user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function hostFromUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            $url = 'https://' . $url;
        }

        $host = parse_url($url, PHP_URL_HOST) ?: $url;
        $host = strtolower(trim((string) $host));
        $host = preg_replace('/^www\./', '', $host) ?: $host;

        return Str::limit($host, 191, '');
    }
}