<?php

namespace App\Services\Drafts;

use DabbaDirect\IntakeTools\HostRetailerGuesser;
use DabbaDirect\IntakeTools\ProductUrlResolver;
use DabbaDirect\IntakeTools\RetailerDetectionResult;
use DabbaDirect\IntakeTools\RetailerTableMatcher;
use DabbaDirect\IntakeTools\UrlTools;
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

        $host = $this->urlTools->normaliseHost($resolved->host ?: $this->urlTools->hostFromUrl($resolved->finalUrl()));
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

        $guessedName = $this->hostGuesser->guess($host);
        if ($guessedName !== null) {
            return new RetailerDetectionResult(
                name: $guessedName,
                host: $host ?: null,
                finalUrl: $resolved->finalUrl(),
                confidence: 0.75,
                source: 'host_fallback',
                warning: $resolved->warning,
                productId: $resolved->productId,
                productIdType: $resolved->productIdType,
                requiresManualReview: true,
                cleanUrl: $resolved->cleanUrl,
                message: 'Retailer was guessed from the URL but was not found in the retailer table.',
            );
        }

        if ($manualRetailerName !== '') {
            return new RetailerDetectionResult(
                name: $manualRetailerName,
                host: $host ?: null,
                finalUrl: $resolved->finalUrl(),
                confidence: 1.0,
                source: 'manual',
                warning: $resolved->warning,
                productId: $resolved->productId,
                productIdType: $resolved->productIdType,
                cleanUrl: $resolved->cleanUrl,
            );
        }

        return new RetailerDetectionResult(
            name: null,
            host: $host ?: null,
            finalUrl: $resolved->finalUrl(),
            confidence: 0.0,
            source: 'unknown',
            warning: $resolved->warning,
            productId: $resolved->productId,
            productIdType: $resolved->productIdType,
            requiresManualReview: true,
            message: 'No retailer could be detected.',
            cleanUrl: $resolved->cleanUrl,
        );
    }

    protected function matchRetailerFromHost(string $host): ?object
    {
        if ($host === '') return null;

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

        return $this->matcher->bestHostMatch($query->get(), $host);
    }
}
