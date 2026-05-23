<?php

namespace App\Services\Drafts;

use DabbaDirect\IntakeTools\HostRetailerGuesser;
use DabbaDirect\IntakeTools\ProductUrlResolver;
use DabbaDirect\IntakeTools\RetailerDetectionResult;
use DabbaDirect\IntakeTools\UrlTools;
use Illuminate\Support\Facades\DB;

class DraftRetailerDetectionService
{
    public function __construct(
        protected ProductUrlResolver $resolver,
        protected UrlTools $urlTools,
        protected HostRetailerGuesser $hostGuesser,
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

        $host = $this->urlTools->normaliseHost($resolved->finalHost ?: $this->urlTools->hostFromUrl($resolved->finalUrl));
        $matched = $this->matchRetailerFromHost($host);

        if ($matched !== null) {
            return new RetailerDetectionResult(
                name: (string) $matched->name,
                host: $host,
                finalUrl: $resolved->finalUrl,
                confidence: 1.0,
                source: 'cms_retailer_table',
                warning: $resolved->warning,
                retailerId: (int) $matched->id,
                baseUrl: (string) $matched->base_url,
            );
        }

        $guessedName = $this->hostGuesser->guess($host);
        if ($guessedName !== null) {
            return new RetailerDetectionResult(
                name: $guessedName,
                host: $host,
                finalUrl: $resolved->finalUrl,
                confidence: 0.75,
                source: 'host_fallback',
                warning: $resolved->warning,
            );
        }

        if ($manualRetailerName !== '') {
            return new RetailerDetectionResult(
                name: $manualRetailerName,
                host: $host,
                finalUrl: $resolved->finalUrl,
                confidence: 1.0,
                source: 'manual',
                warning: $resolved->warning,
            );
        }

        return new RetailerDetectionResult(
            name: null,
            host: $host ?: null,
            finalUrl: $resolved->finalUrl,
            confidence: 0.0,
            source: 'unknown',
            warning: $resolved->warning,
        );
    }

    protected function matchRetailerFromHost(string $host): ?object
    {
        if ($host === '') return null;

        $retailers = DB::table('retailers')
            ->where('is_active', 1)
            ->whereNull('deleted_at')
            ->select('id', 'name', 'base_url')
            ->get();

        $best = null;
        $bestLength = 0;

        foreach ($retailers as $retailer) {
            $retailerHost = $this->urlTools->hostFromUrl((string) $retailer->base_url)
                ?: $this->urlTools->normaliseHost((string) $retailer->base_url);

            if ($retailerHost === '') continue;

            $matches = $host === $retailerHost || str_ends_with($host, '.' . $retailerHost);
            if ($matches && strlen($retailerHost) > $bestLength) {
                $best = $retailer;
                $bestLength = strlen($retailerHost);
            }
        }

        return $best;
    }
}
