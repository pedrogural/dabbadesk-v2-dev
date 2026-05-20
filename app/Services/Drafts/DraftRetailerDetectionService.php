<?php

namespace App\Services\Drafts;

use DabbaDirect\IntakeTools\HostRetailerGuesser;
use DabbaDirect\IntakeTools\ProductUrlResolver;
use DabbaDirect\IntakeTools\RetailerDetectionResult;
use DabbaDirect\IntakeTools\UrlTools;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
            return $this->resultFromRetailer($matched, $host, $resolved->finalUrl, 'retailer_table_host', 1.0, $resolved->warning);
        }

        $guessedName = $this->hostGuesser->guess($host);
        if ($guessedName !== null) {
            $matchedByName = $this->matchRetailerFromName($guessedName);
            if ($matchedByName !== null) {
                return $this->resultFromRetailer($matchedByName, $host, $resolved->finalUrl, 'shared_host_guess_name_match', 0.95, $resolved->warning);
            }

            return new RetailerDetectionResult(
                name: $guessedName,
                host: $host,
                finalUrl: $resolved->finalUrl,
                confidence: 0.75,
                source: 'shared_host_guess',
                warning: $resolved->warning,
            );
        }

        if ($manualRetailerName !== '') {
            $matchedByManual = $this->matchRetailerFromName($manualRetailerName);
            if ($matchedByManual !== null) {
                return $this->resultFromRetailer($matchedByManual, $host, $resolved->finalUrl, 'manual_name_match', 1.0, $resolved->warning);
            }

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
        if ($host === '' || ! Schema::hasTable('retailers')) return null;

        $best = null;
        $bestScore = 0;

        foreach ($this->retailerBaseQuery()->select('id', 'name', 'base_url')->get() as $retailer) {
            $retailerHost = $this->urlTools->hostOrUrlToHost((string) ($retailer->base_url ?? ''));
            if ($retailerHost === '') continue;

            $matches = $host === $retailerHost
                || str_ends_with($host, '.' . $retailerHost)
                || str_ends_with($retailerHost, '.' . $host)
                || str_contains($host, $retailerHost)
                || str_contains($retailerHost, $host);

            if ($matches) {
                $score = strlen($retailerHost);
                if ($score > $bestScore) {
                    $best = $retailer;
                    $bestScore = $score;
                }
            }
        }

        return $best;
    }

    protected function matchRetailerFromName(string $name): ?object
    {
        if (trim($name) === '' || ! Schema::hasTable('retailers')) return null;

        $cleanName = $this->normaliseName($name);
        if ($cleanName === '') return null;

        $retailers = $this->retailerBaseQuery()->select('id', 'name', 'base_url')->get();

        foreach ($retailers as $retailer) {
            $retailerName = $this->normaliseName((string) $retailer->name);
            if ($retailerName === $cleanName) return $retailer;
        }

        foreach ($retailers as $retailer) {
            $retailerName = $this->normaliseName((string) $retailer->name);
            if ($retailerName !== '' && (str_contains($retailerName, $cleanName) || str_contains($cleanName, $retailerName))) {
                return $retailer;
            }
        }

        return null;
    }

    protected function retailerBaseQuery()
    {
        $query = DB::table('retailers');

        if (Schema::hasColumn('retailers', 'is_active')) {
            $query->where('is_active', 1);
        } elseif (Schema::hasColumn('retailers', 'active')) {
            $query->where('active', 1);
        }

        if (Schema::hasColumn('retailers', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query;
    }

    protected function resultFromRetailer(object $retailer, string $host, string $finalUrl, string $source, float $confidence, ?string $warning): RetailerDetectionResult
    {
        return new RetailerDetectionResult(
            name: (string) $retailer->name,
            host: $host,
            finalUrl: $finalUrl,
            confidence: $confidence,
            source: $source,
            warning: $warning,
            retailerId: (int) $retailer->id,
            baseUrl: (string) $retailer->base_url,
        );
    }

    protected function normaliseName(string $name): string
    {
        $name = strtolower(trim($name));
        $name = preg_replace('/[^a-z0-9]+/', '', $name) ?: '';
        $name = preg_replace('/(uk|gb|gbr)$/', '', $name) ?: $name;
        return $name;
    }
}