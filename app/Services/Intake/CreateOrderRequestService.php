<?php

namespace App\Services\Intake;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateOrderRequestService
{
    public function create(array $payload, Request $request): array
    {
        return DB::transaction(function () use ($payload, $request) {
            $submissionUuid = trim((string) ($payload['submission_uuid'] ?? ''));

            if ($submissionUuid !== '') {
                $existing = DB::table('order_requests')
                    ->where('submission_uuid', $submissionUuid)
                    ->first();

                if ($existing) {
                    return [
                        'id' => $existing->id,
                        'request_ref' => $existing->request_ref,
                        'existing' => true,
                    ];
                }
            }

            $requestRef = $this->nextRequestRef();

            $nameParts = $this->splitName((string) ($payload['customer_name'] ?? ''));

            $estimatedTotal = collect($payload['items'] ?? [])
                ->sum(function (array $item): float {
                    $qty = max(1, (int) ($item['qty'] ?? 1));
                    $price = (float) ($item['estimated_price'] ?? 0);

                    return round($qty * $price, 2);
                });

            $orderRequestId = DB::table('order_requests')->insertGetId([
                'request_ref' => $requestRef,
                'source' => (string) ($payload['source'] ?? 'order_app_v2'),
                'reference_number' => null,

                'customer_first_name' => $nameParts['first_name'],
                'customer_last_name' => $nameParts['last_name'],
                'customer_company_name' => $payload['customer_company_name'] ?? null,
                'customer_email' => $payload['customer_email'] ?? null,

                'customer_phone_country_id' => null,
                'customer_phone_digits' => $this->digitsOnly((string) ($payload['customer_phone'] ?? '')),

                'customer_address_line1' => $payload['address_line1'] ?? null,
                'customer_address_postcode' => $payload['address_postcode'] ?? null,
                'customer_address_country_id' => null,

                'notes' => $payload['notes'] ?? null,
                'status' => 'received',
                'estimated_total' => $estimatedTotal,

                'disclaimer_accepted_at' => now(),
                'submitted_at' => now(),
                'submitted_ip' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 255, ''),

                'submission_uuid' => $submissionUuid !== '' ? $submissionUuid : null,

                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach (($payload['items'] ?? []) as $index => $item) {
                $qty = max(1, (int) ($item['qty'] ?? 1));
                $unitPrice = round((float) ($item['estimated_price'] ?? 0), 2);

                DB::table('order_request_items')->insert([
                    'order_request_id' => $orderRequestId,
                    'retailer_id' => null,
                    'retailer_name' => $item['retailer_name'] ?? null,
                    'retailer_url' => $item['retailer_url'] ?? null,
                    'product_code' => $item['product_code'] ?? null,
                    'description' => $this->descriptionForItem($item),
                    'unit_price' => $unitPrice,
                    'quantity' => $qty,
                    'line_total' => round($qty * $unitPrice, 2),
                    'notes' => $item['notes'] ?? null,
                    'sort_order' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return [
                'id' => $orderRequestId,
                'request_ref' => $requestRef,
                'existing' => false,
            ];
        });
    }

    private function nextRequestRef(): string
    {
        $latest = DB::table('order_requests')
            ->whereNotNull('request_ref')
            ->whereRaw("request_ref REGEXP '^[0-9]+$'")
            ->lockForUpdate()
            ->max(DB::raw('CAST(request_ref AS UNSIGNED)'));

        return (string) (((int) $latest) + 1);
    }

    private function splitName(string $fullName): array
    {
        $fullName = trim(preg_replace('/\s+/', ' ', $fullName) ?: '');

        if ($fullName === '') {
            return [
                'first_name' => null,
                'last_name' => null,
            ];
        }

        $parts = explode(' ', $fullName, 2);

        return [
            'first_name' => $parts[0] ?? null,
            'last_name' => $parts[1] ?? null,
        ];
    }

    private function digitsOnly(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        return $digits !== '' ? $digits : null;
    }

    private function descriptionForItem(array $item): string
    {
        $description = trim((string) ($item['description'] ?? ''));

        if ($description !== '') {
            return Str::limit($description, 500, '');
        }

        $code = trim((string) ($item['product_code'] ?? ''));

        if ($code !== '') {
            return Str::limit($code, 500, '');
        }

        $retailer = trim((string) ($item['retailer_name'] ?? ''));

        return Str::limit('Item from ' . ($retailer !== '' ? $retailer : 'unknown retailer'), 500, '');
    }
}
