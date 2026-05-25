<?php

namespace App\Services\Intake;

use DabbaDirect\IntakeTools\HostRetailerGuesser;
use DabbaDirect\IntakeTools\ProductUrlResolver;
use DabbaDirect\IntakeTools\RetailerTableMatcher;
use DabbaDirect\IntakeTools\UrlTools;
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
        $host = $resolved?->host ?: $this->urlTools->hostFromUrl($url);

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

        if (! $matched && $retailerName !== '') {
            $matched = $this->findByName($retailerName);
            if ($matched) {
                $confidence = 'medium';
                $matchedBy = 'retailer_name';
            }
        }

        if ($matched) {
            $matchedHost = $this->urlTools->hostFromUrl((string) ($matched->base_url ?? '')) ?: $host;

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
            $fallbackName = $this->hostGuesser->guess($host) ?: 'Unknown retailer';

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
        $host = $this->urlTools->normaliseHost($host);
        if ($host === '') return null;

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

        return $this->matcher->bestHostMatch($query->get(), $host);
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
            ->where(function ($query) use ($name) {
                $query->where('name', $name)->orWhere('name', 'like', '%' . $name . '%');
            })
            ->orderByRaw('CASE WHEN name = ? THEN 0 ELSE 1 END', [$name])
            ->first();
    }
}
