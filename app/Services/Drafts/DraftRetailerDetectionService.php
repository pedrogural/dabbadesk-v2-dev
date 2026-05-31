<?php

namespace App\Services\Drafts;

use DabbaDirect\IntakeTools\HostRetailerGuesser;
use DabbaDirect\IntakeTools\ProductUrlResolver;
use DabbaDirect\IntakeTools\RetailerDetectionResult;
use DabbaDirect\IntakeTools\RetailerTableMatcher;
use DabbaDirect\IntakeTools\UrlTools;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DraftRetailerDetectionService
{
    public function __construct(
        protected ProductUrlResolver $resolver,
        protected UrlTools $urlTools,
        protected HostRetailerGuesser $hostGuesser,
        protected RetailerTableMatcher $matcher,
    ) {}

    public function detect(?string $url, ?string $manualRetailerName = null): RetailerDetectionResult
    {
        $manualRetailerName = trim((string) $manualRetailerName);
        $resolved = $this->resolver->resolve($url);

        if ($resolved === null) {
            return new RetailerDetectionResult(
                name: $manualRetailerName !== '' ? $manualRetailerName : null,
                confidence: $manualRetailerName !== '' ? 1.0 : 0.0,
                source: $manualRetailerName !== '' ? 'manual' : 'empty',
            );
        }

        $host = $this->normaliseHost($resolved->host ?: $resolved->finalUrl() ?: (string) $url);
        $matched = $this->matchRetailerFromHost($host);

        if ($matched !== null) {
            return new RetailerDetectionResult(
                name: (string) $matched->name,
                host: $host ?: null,
                finalUrl: $resolved->finalUrl(),
                confidence: 1.0,
                source: 'cms_retailer_table',
                warning: $resolved->warning,
                retailerId: (int) $matched->id,
                baseUrl: (string) ($matched->base_url ?? ''),
                logoPath: $matched->logo_path ?? null,
                productId: $resolved->productId,
                productIdType: $resolved->productIdType,
                requiresManualReview: false,
                cleanUrl: $resolved->cleanUrl,
                message: 'Retailer matched from DabbaDesk retailer table.',
            );
        }

        $guessedName = $manualRetailerName !== ''
            ? $manualRetailerName
            : ($this->hostGuesser->guess($host) ?: $this->friendlyNameFromHost($host));

        return new RetailerDetectionResult(
            name: $guessedName ?: null,
            host: $host ?: null,
            finalUrl: $resolved->finalUrl(),
            confidence: $guessedName ? 0.75 : 0.0,
            source: $guessedName ? 'host_fallback' : 'unknown',
            warning: $resolved->warning,
            productId: $resolved->productId,
            productIdType: $resolved->productIdType,
            requiresManualReview: true,
            cleanUrl: $resolved->cleanUrl,
            message: $guessedName
                ? 'Retailer was guessed from the URL but was not found in the retailer table.'
                : 'No retailer could be detected.',
        );
    }

    protected function matchRetailerFromHost(string $host): ?object
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
            ])));

        if (Schema::hasColumn('retailers', 'is_active')) {
            $query->where('is_active', 1);
        } elseif (Schema::hasColumn('retailers', 'active')) {
            $query->where('active', 1);
        }

        if (Schema::hasColumn('retailers', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $this->bestHostMatch($query->get(), $host);
    }

    private function bestHostMatch(Collection $retailers, string $host): ?object
    {
        $host = $this->normaliseHost($host);
        $best = null;
        $bestLength = -1;

        foreach ($retailers as $retailer) {
            $baseHost = $this->normaliseHost((string) ($retailer->base_url ?? ''));
            if ($baseHost === '') {
                continue;
            }

            $isMatch = $host === $baseHost || str_ends_with($host, '.' . $baseHost);
            if (! $isMatch) {
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
