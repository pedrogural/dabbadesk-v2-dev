<?php

namespace App\Services\Intake;

use Illuminate\Http\Request;
use App\Support\Text\TextNormalizer;
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
            $purchaseMode = $this->normalisePurchaseMode((string) ($payload['purchase_mode'] ?? $payload['order_type'] ?? 'standard'));

            $payload = $this->normalisePayloadText($payload);

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
                'purchase_mode' => $purchaseMode,

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
                    'retailer_id' => $this->resolveRetailerId($item),
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

    private function normalisePayloadText(array $payload): array
    {
        foreach ([
            'customer_name' => 191,
            'customer_company_name' => 150,
            'customer_email' => 255,
            'customer_phone' => 40,
            'address_line1' => 191,
            'address_postcode' => 32,
            'notes' => 5000,
        ] as $key => $limit) {
            if (array_key_exists($key, $payload)) {
                $payload[$key] = TextNormalizer::cleanOrNull(is_scalar($payload[$key]) ? (string) $payload[$key] : null, $limit);
            }
        }

        foreach (($payload['items'] ?? []) as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            foreach ([
                'retailer_name' => 191,
                'retailer_url' => 2048,
                'product_code' => 150,
                'description' => 2000,
                'notes' => 5000,
            ] as $key => $limit) {
                if (array_key_exists($key, $item)) {
                    $payload['items'][$index][$key] = TextNormalizer::cleanOrNull(is_scalar($item[$key]) ? (string) $item[$key] : null, $limit);
                }
            }
        }

        return $payload;
    }

    private function normalisePurchaseMode(string $value): string
    {
        $value = trim(strtolower($value));

        return in_array($value, ['customer_self_purchase', 'self_purchase', 'customer_purchase'], true)
            ? 'customer_self_purchase'
            : 'standard';
    }

    private function nextRequestRef(): string
    {
        $counter = DB::table('order_ref_counter')
            ->where('id', 1)
            ->lockForUpdate()
            ->first();

        $latestNumericRef = DB::table('order_requests')
            ->whereNotNull('request_ref')
            ->whereRaw("request_ref REGEXP '^[0-9]+$'")
            ->max(DB::raw('CAST(request_ref AS UNSIGNED)'));

        $nextValue = max(
            (int) ($counter->next_value ?? 0),
            ((int) $latestNumericRef) + 1
        );

        if ($counter) {
            DB::table('order_ref_counter')
                ->where('id', 1)
                ->update(['next_value' => $nextValue + 1]);
        } else {
            DB::table('order_ref_counter')->insert([
                'id' => 1,
                'next_value' => $nextValue + 1,
            ]);
        }

        return (string) $nextValue;
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


    private function resolveRetailerId(array $item): ?int
    {
        $candidate = $item['retailer_id'] ?? null;

        if (is_numeric($candidate) && (int) $candidate > 0) {
            $exists = DB::table('retailers')
                ->where('id', (int) $candidate)
                ->where(function ($query) {
                    $query->where('is_active', 1)
                        ->orWhereNull('is_active');
                })
                ->whereNull('deleted_at')
                ->exists();

            if ($exists) {
                return (int) $candidate;
            }
        }

        $host = $this->normaliseHost((string) ($item['retailer_url'] ?? ''));

        if ($host !== '') {
            $retailer = DB::table('retailers')
                ->whereNull('deleted_at')
                ->where(function ($query) {
                    $query->where('is_active', 1)
                        ->orWhereNull('is_active');
                })
                ->where(function ($query) use ($host) {
                    $query->where('base_url', $host)
                        ->orWhere('base_url', 'www.' . $host);
                })
                ->first();

            if ($retailer) {
                return (int) $retailer->id;
            }
        }

        $name = trim((string) ($item['retailer_name'] ?? ''));

        if ($name !== '') {
            $retailer = DB::table('retailers')
                ->whereNull('deleted_at')
                ->where(function ($query) {
                    $query->where('is_active', 1)
                        ->orWhereNull('is_active');
                })
                ->where('name', $name)
                ->first();

            if ($retailer) {
                return (int) $retailer->id;
            }
        }

        return null;
    }

    private function normaliseHost(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        if (! str_contains($url, '://')) {
            $url = 'https://' . $url;
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host) || trim($host) === '') {
            return '';
        }

        $host = strtolower(trim($host));
        $host = preg_replace('/^www\./', '', $host) ?: $host;

        return $host;
    }

    private function descriptionForItem(array $item): string
    {
        $description = trim((string) TextNormalizer::clean($item['description'] ?? '', 500));

        if ($description !== '') {
            return Str::limit($description, 500, '');
        }

        $code = trim((string) TextNormalizer::clean($item['product_code'] ?? '', 500));

        if ($code !== '') {
            return Str::limit($code, 500, '');
        }

        $retailer = trim((string) TextNormalizer::clean($item['retailer_name'] ?? '', 500));

        return Str::limit('Item from ' . ($retailer !== '' ? $retailer : 'unknown retailer'), 500, '');
    }
}
