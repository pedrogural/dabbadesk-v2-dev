<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LegacyTextCleanupService
{
    /**
     * Only these table/field pairs may be scanned or updated.
     * Keep this explicit: the inspector is admin-only, but still must be safe.
     */
    public function targets(): array
    {
        return [
            'Customer master data' => [
                ['table' => 'customers', 'field' => 'first_name', 'label' => 'Customer first name'],
                ['table' => 'customers', 'field' => 'last_name', 'label' => 'Customer last name'],
                ['table' => 'customers', 'field' => 'company_name', 'label' => 'Customer company'],
                ['table' => 'addresses', 'field' => 'line1', 'label' => 'Address line 1'],
                ['table' => 'addresses', 'field' => 'line2', 'label' => 'Address line 2'],
                ['table' => 'addresses', 'field' => 'city', 'label' => 'Address city'],
                ['table' => 'addresses', 'field' => 'region', 'label' => 'Address region'],
            ],
            'Order snapshots' => [
                ['table' => 'orders', 'field' => 'bill_to_name', 'label' => 'Order billing name'],
                ['table' => 'orders', 'field' => 'bill_to_company', 'label' => 'Order billing company'],
                ['table' => 'orders', 'field' => 'bill_to_address_line1', 'label' => 'Order billing address'],
            ],
            'Order items' => [
                ['table' => 'order_items', 'field' => 'item_name', 'label' => 'Order item name'],
                ['table' => 'order_items', 'field' => 'description', 'label' => 'Order item description'],
                ['table' => 'order_items', 'field' => 'product_code', 'label' => 'Order item product code'],
                ['table' => 'order_items', 'field' => 'marketplace_seller', 'label' => 'Order item seller'],
            ],
            'Draft items' => [
                ['table' => 'draft_order_items', 'field' => 'description', 'label' => 'Draft item description'],
                ['table' => 'draft_order_items', 'field' => 'product_code', 'label' => 'Draft item product code'],
                ['table' => 'draft_order_items', 'field' => 'sku', 'label' => 'Draft item SKU'],
            ],
            'Invoice snapshots' => [
                ['table' => 'invoice_version_items', 'field' => 'description', 'label' => 'Invoice item description'],
                ['table' => 'invoice_version_items', 'field' => 'sku', 'label' => 'Invoice item SKU'],
            ],
            'Order requests' => [
                ['table' => 'order_requests', 'field' => 'customer_first_name', 'label' => 'Request first name'],
                ['table' => 'order_requests', 'field' => 'customer_last_name', 'label' => 'Request last name'],
                ['table' => 'order_requests', 'field' => 'customer_company_name', 'label' => 'Request company'],
                ['table' => 'order_requests', 'field' => 'customer_address_line1', 'label' => 'Request address'],
                ['table' => 'order_request_items', 'field' => 'description', 'label' => 'Request item description'],
                ['table' => 'order_request_items', 'field' => 'product_code', 'label' => 'Request item product code'],
                ['table' => 'order_request_items', 'field' => 'retailer_name', 'label' => 'Request retailer name'],
            ],
        ];
    }

    public function flattenedTargets(): array
    {
        $items = [];

        foreach ($this->targets() as $group => $targets) {
            foreach ($targets as $target) {
                if (! Schema::hasTable($target['table']) || ! Schema::hasColumn($target['table'], $target['field'])) {
                    continue;
                }

                $target['group'] = $group;
                $target['key'] = $this->targetKey($target['table'], $target['field']);
                $items[$target['key']] = $target;
            }
        }

        return $items;
    }

    public function findTarget(string $table, string $field): ?array
    {
        foreach ($this->flattenedTargets() as $target) {
            if ($target['table'] === $table && $target['field'] === $field) {
                return $target;
            }
        }

        return null;
    }

    public function scan(?string $targetKey = null, ?string $search = null, int $limit = 200): array
    {
        $limit = max(1, min($limit, 500));
        $targets = $this->flattenedTargets();

        if ($targetKey && isset($targets[$targetKey])) {
            $targets = [$targetKey => $targets[$targetKey]];
        }

        $results = [];

        foreach ($targets as $target) {
            $remaining = $limit - count($results);

            if ($remaining <= 0) {
                break;
            }

            $results = array_merge($results, $this->scanTarget($target, $search, $remaining));
        }

        return $results;
    }

    public function scanTarget(array $target, ?string $search, int $limit): array
    {
        $field = $target['field'];

        $query = DB::table($target['table'])
            ->select('id', $field)
            ->whereNotNull($field)
            ->where($field, '<>', '')
            ->where(function ($query) use ($field) {
                foreach ($this->badNeedles() as $needle) {
                    $query->orWhere($field, 'like', '%' . $needle . '%');
                }
            })
            ->orderBy('id')
            // Pull a little extra because we now apply a stricter PHP check below.
            ->limit($limit * 4);

        if ($search !== null && trim($search) !== '') {
            $query->where($field, 'like', '%' . trim($search) . '%');
        }

        $rows = [];

        foreach ($query->get() as $row) {
            $current = (string) $row->{$field};

            // Important: the database LIKE scan is only a cheap first pass.
            // This stricter check stops clean rows being shown just because a broad pattern matched oddly.
            if (! $this->looksSuspicious($current)) {
                continue;
            }

            $rows[] = [
                'id' => (int) $row->id,
                'table' => $target['table'],
                'field' => $field,
                'label' => $target['label'],
                'group' => $target['group'],
                'current' => $current,
                'context' => $this->contextFor($target['table'], (int) $row->id),
            ];

            if (count($rows) >= $limit) {
                break;
            }
        }

        return $rows;
    }

    public function updateText(string $table, string $field, int $id, string $original, string $replacement): void
    {
        $target = $this->findTarget($table, $field);

        if (! $target) {
            abort(403, 'This field is not approved for legacy text inspection.');
        }

        $current = DB::table($table)->where('id', $id)->value($field);

        if ((string) $current !== $original) {
            abort(409, 'This text changed since the inspector screen was loaded. Refresh and try again.');
        }

        $updates = [$field => $replacement];

        if (Schema::hasColumn($table, 'updated_at')) {
            $updates['updated_at'] = now();
        }

        DB::table($table)->where('id', $id)->update($updates);
    }

    public function looksSuspicious(string $text): bool
    {
        if ($text === '') {
            return false;
        }

        foreach ($this->badNeedles() as $needle) {
            if ($needle !== '' && str_contains($text, $needle)) {
                return true;
            }
        }

        // Unicode replacement character: often means decoding damage.
        if (str_contains($text, '�')) {
            return true;
        }

        // Common mojibake clusters, deliberately stricter than a generic "accent" search.
        return (bool) preg_match('/(Ãƒ|Ã‚|Ã¢|Â£|Â°|â€|â€™|â€œ|â€“|â€”|â‚¬)/u', $text);
    }

    public function badNeedles(): array
    {
        return [
            'Ãƒ',
            'Ã‚',
            'Ã¢',
            'Ã©',
            'Ã¨',
            'Ã¡',
            'Ã­',
            'Ã³',
            'Ãº',
            'Ã±',
            'Â£',
            'Â°',
            'Â ',
            'â€',
            'â€™',
            'â€œ',
            'â€',
            'â€“',
            'â€”',
            'â‚¬',
            '�',
        ];
    }

    public function helperReplacements(): array
    {
        return [
            'Try common cleanup' => [
                '360Ãƒâ€šÃ‚°' => '360°',
                'Ãƒâ€šÃ‚°' => '°',
                'Ã‚°' => '°',
                'Ãƒâ€šÃ‚£' => '£',
                'Ã‚£' => '£',
                'Â£' => '£',
                'â€™' => "'",
                'â€˜' => "'",
                'â€œ' => '"',
                'â€' => '"',
                'â€“' => '-',
                'â€”' => '-',
                'Â ' => ' ',
                'Â' => '',
            ],
            'Degree symbol' => [
                '360Ãƒâ€šÃ‚°' => '360°',
                'Ãƒâ€šÃ‚°' => '°',
                'Ã‚°' => '°',
                'Â°' => '°',
            ],
            'Smart apostrophe' => [
                'â€™' => "'",
                'â€˜' => "'",
            ],
            'Smart quotes' => [
                'â€œ' => '"',
                'â€' => '"',
            ],
            'Dashes' => [
                'â€“' => '-',
                'â€”' => '-',
            ],
            'Pound sign' => [
                'Ãƒâ€šÃ‚£' => '£',
                'Ã‚£' => '£',
                'Â£' => '£',
            ],
        ];
    }

    public function targetKey(string $table, string $field): string
    {
        return $table . '.' . $field;
    }

    private function contextFor(string $table, int $id): ?string
    {
        return match ($table) {
            'orders' => optional(DB::table('orders')->select('order_number', 'bill_to_name')->where('id', $id)->first(), function ($row) {
                return trim('Order #' . ($row->order_number ?? '') . ' ' . ($row->bill_to_name ?? ''));
            }),
            'order_items' => optional(DB::table('order_items')->select('order_id')->where('id', $id)->first(), function ($row) {
                $orderNumber = DB::table('orders')->where('id', $row->order_id)->value('order_number');
                return $orderNumber ? 'Order #' . $orderNumber : 'Order ID ' . $row->order_id;
            }),
            'draft_order_items' => optional(DB::table('draft_order_items')->select('draft_order_id')->where('id', $id)->first(), fn ($row) => 'Draft ID ' . $row->draft_order_id),
            'invoice_version_items' => optional(DB::table('invoice_version_items')->select('invoice_version_id')->where('id', $id)->first(), fn ($row) => 'Invoice version ID ' . $row->invoice_version_id),
            'order_request_items' => optional(DB::table('order_request_items')->select('order_request_id')->where('id', $id)->first(), fn ($row) => 'Request ID ' . $row->order_request_id),
            default => null,
        };
    }
}
