<?php

namespace App\Services\Intake;

use DabbaDirect\IntakeTools\HostRetailerGuesser;
use DabbaDirect\IntakeTools\ProductUrlResolver;
use DabbaDirect\IntakeTools\RetailerTableMatcher;
use DabbaDirect\IntakeTools\UrlTools;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RetailerLookupService
{
    public function __construct(
        protected ProductUrlResolver $resolver,
        protected UrlTools $urlTools,
        protected HostRetailerGuesser $hostGuesser,
        protected RetailerTableMatcher $matcher,
    ) {}

    public function detect(string $url = '', string $productCode = '', string $retailerName = ''): array
    {
        $url = trim($url);
        $retailerName = trim($retailerName);
        $resolved = $this->resolver->resolve($url);
        $finalUrl = $resolved?->finalUrl() ?: ($url !== '' ? $url : null);
        $cleanUrl = $resolved?->cleanUrl ?: $finalUrl;
        $host = $this->normaliseHost($resolved?->host ?: $finalUrl ?: $url);

        $matched = null;
        $confidence = 'none';
        $matchedBy = null;

        if ($host !== '') {
            $matched = $this->findByHost($host);
            if ($matched) {
                $confidence = 'high';
                $matchedBy = 'url_host';
            }
        }

        // Only fall back to a name match when no usable URL host exists.
        // A typed/guessed retailer name must never override a real product domain.
        if (! $matched && $host === '' && $retailerName !== '') {
            $matched = $this->findByName($retailerName);
            if ($matched) {
                $confidence = 'medium';
                $matchedBy = 'retailer_name';
            }
        }

        if ($matched) {
            $matchedHost = $this->normaliseHost((string) ($matched->base_url ?? '')) ?: $host;

            return [
                'matched' => true,
                'confidence' => $confidence,
                'matched_by' => $matchedBy,
                'id' => (int) $matched->id,
                'retailer_id' => (int) $matched->id,
                'name' => (string) $matched->name,
                'code' => Str::slug((string) $matched->name),
                'base_url' => $matched->base_url ?? null,
                'host' => $matchedHost ?: $host,
                'final_host' => $host ?: $matchedHost,
                'final_url' => $finalUrl,
                'finalUrl' => $finalUrl,
                'clean_url' => $cleanUrl,
                'cleanUrl' => $cleanUrl,
                'logo_path' => $matched->logo_path ?? null,
                'active' => (bool) ($matched->is_active ?? $matched->active ?? true),
                'requires_manual_review' => false,
                'product_id' => $resolved?->productId,
                'product_id_type' => $resolved?->productIdType,
                'message' => 'Retailer matched from DabbaDesk retailer table.',
            ];
        }

        if ($url !== '') {
            $fallbackName = $retailerName !== ''
                ? $retailerName
                : ($this->hostGuesser->guess($host) ?: $this->friendlyNameFromHost($host) ?: 'Unknown retailer');

            return [
                'matched' => false,
                'confidence' => 'low',
                'matched_by' => 'url_fallback',
                'id' => null,
                'retailer_id' => null,
                'name' => $fallbackName,
                'code' => Str::slug($fallbackName),
                'base_url' => $host ?: null,
                'host' => $host ?: null,
                'final_host' => $host ?: null,
                'final_url' => $finalUrl,
                'finalUrl' => $finalUrl,
                'clean_url' => $cleanUrl,
                'cleanUrl' => $cleanUrl,
                'logo_path' => null,
                'active' => true,
                'requires_manual_review' => true,
                'product_id' => $resolved?->productId,
                'product_id_type' => $resolved?->productIdType,
                'message' => 'Retailer was guessed from the URL but was not found in the retailer table.',
            ];
        }

        if ($retailerName !== '') {
            return [
                'matched' => false,
                'confidence' => 'low',
                'matched_by' => 'name_fallback',
                'id' => null,
                'retailer_id' => null,
                'name' => Str::title($retailerName),
                'code' => Str::slug($retailerName),
                'base_url' => null,
                'host' => null,
                'final_host' => null,
                'final_url' => null,
                'finalUrl' => null,
                'clean_url' => null,
                'cleanUrl' => null,
                'logo_path' => null,
                'active' => true,
                'requires_manual_review' => true,
                'message' => 'Retailer name was provided but was not found in the retailer table.',
            ];
        }

        return [
            'matched' => false,
            'confidence' => 'none',
            'matched_by' => null,
            'id' => null,
            'retailer_id' => null,
            'name' => null,
            'code' => null,
            'base_url' => null,
            'host' => $host ?: null,
            'final_host' => $host ?: null,
            'final_url' => $finalUrl,
            'finalUrl' => $finalUrl,
            'clean_url' => $cleanUrl,
            'cleanUrl' => $cleanUrl,
            'logo_path' => null,
            'active' => false,
            'requires_manual_review' => true,
            'message' => 'No retailer could be detected.',
        ];
    }

    private function findByHost(string $host): ?object
    {
        $host = $this->normaliseHost($host);
        if ($host === '') {
            return null;
        }

        $query = DB::table('retailers')
            ->select(array_values(array_filter([
                'id',
                'name',
                'base_url',
                Schema::hasColumn('retailers', 'logo_path') ? 'logo_path' : null,
                Schema::hasColumn('retailers', 'is_active') ? 'is_active' : null,
                Schema::hasColumn('retailers', 'active') ? 'active' : null,
            ])));

        if (Schema::hasColumn('retailers', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }
        if (Schema::hasColumn('retailers', 'is_active')) {
            $query->where('is_active', 1);
        } elseif (Schema::hasColumn('retailers', 'active')) {
            $query->where('active', 1);
        }

        return $this->bestHostMatch($query->get(), $host);
    }

    private function bestHostMatch(Collection $retailers, string $host): ?object
    {
        $best = null;
        $bestLength = -1;

        foreach ($retailers as $retailer) {
            $baseHost = $this->normaliseHost((string) ($retailer->base_url ?? ''));
            if ($baseHost === '') {
                continue;
            }

            if ($host !== $baseHost && ! str_ends_with($host, '.' . $baseHost)) {
                continue;
            }

            $length = strlen($baseHost);
            if ($length > $bestLength) {
                $best = $retailer;
                $bestLength = $length;
            }
        }

        return $best;
    }

    private function findByName(string $name): ?object
    {
        $name = trim($name);
        if ($name === '') return null;

        $query = DB::table('retailers')
            ->select(array_values(array_filter([
                'id',
                'name',
                'base_url',
                Schema::hasColumn('retailers', 'logo_path') ? 'logo_path' : null,
                Schema::hasColumn('retailers', 'is_active') ? 'is_active' : null,
                Schema::hasColumn('retailers', 'active') ? 'active' : null,
            ])));

        if (Schema::hasColumn('retailers', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }
        if (Schema::hasColumn('retailers', 'is_active')) {
            $query->where('is_active', 1);
        } elseif (Schema::hasColumn('retailers', 'active')) {
            $query->where('active', 1);
        }

        return $query
            ->where('name', $name)
            ->first();
    }

    private function normaliseHost(string $value): string
    {
        $value = strtolower(trim($value));
        if ($value === '') {
            return '';
        }

        if (! str_contains($value, '://')) {
            $value = 'https://' . $value;
        }

        $host = parse_url($value, PHP_URL_HOST) ?: $value;
        $host = strtolower(trim((string) $host));
        $host = preg_replace('/^www\./', '', $host) ?: $host;
        $host = trim($host, " \t\n\r\0\x0B/");

        return $host;
    }

    private function friendlyNameFromHost(string $host): string
    {
        $host = $this->normaliseHost($host);
        if ($host === '') {
            return '';
        }

        $first = explode('.', $host)[0] ?? '';
        $first = str_replace(['-', '_'], ' ', $first);

        return trim(ucwords($first));
    }
}
