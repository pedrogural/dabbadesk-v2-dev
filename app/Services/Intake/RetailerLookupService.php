<?php

namespace App\Services\Intake;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RetailerLookupService
{
    public function detect(string $url = '', string $productCode = '', string $retailerName = ''): array
    {
        $url = trim($url);
        $productCode = trim($productCode);
        $retailerName = trim($retailerName);

        $host = $this->hostFromUrl($url);

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

        if (! $matched && $url !== '') {
            $fallbackName = $host !== '' ? $this->guessReadableName($url) : 'Unknown retailer';

            return [
                'matched' => false,
                'confidence' => 'low',
                'matched_by' => 'url_fallback',
                'id' => null,
                'name' => $fallbackName,
                'code' => Str::slug($fallbackName),
                'base_url' => $host ?: null,
                'host' => $host ?: null,
                'logo_path' => null,
                'active' => true,
                'requires_manual_review' => true,
                'message' => 'Retailer was guessed from the URL but was not found in the retailer table.',
            ];
        }

        if (! $matched && $retailerName !== '') {
            return [
                'matched' => false,
                'confidence' => 'low',
                'matched_by' => 'name_fallback',
                'id' => null,
                'name' => Str::title($retailerName),
                'code' => Str::slug($retailerName),
                'base_url' => null,
                'host' => null,
                'logo_path' => null,
                'active' => true,
                'requires_manual_review' => true,
                'message' => 'Retailer name was provided but was not found in the retailer table.',
            ];
        }

        if (! $matched) {
            return [
                'matched' => false,
                'confidence' => 'none',
                'matched_by' => null,
                'id' => null,
                'name' => null,
                'code' => null,
                'base_url' => null,
                'host' => $host ?: null,
                'logo_path' => null,
                'active' => false,
                'requires_manual_review' => true,
                'message' => 'No retailer could be detected.',
            ];
        }

        $matchedHost = $this->hostFromUrl((string) ($matched->base_url ?? ''));

        return [
            'matched' => true,
            'confidence' => $confidence,
            'matched_by' => $matchedBy,
            'id' => $matched->id,
            'name' => $matched->name,
            'code' => Str::slug($matched->name),
            'base_url' => $matched->base_url ?? null,
            'host' => $matchedHost ?: $host,
            'logo_path' => $matched->logo_path ?? null,
            'active' => (bool) ($matched->is_active ?? true),
            'requires_manual_review' => false,
            'message' => 'Retailer matched from DabbaDesk retailer table.',
        ];
    }

    private function findByHost(string $host): ?object
    {
        $host = $this->cleanHost($host);

        if ($host === '') {
            return null;
        }

        $hostWithoutSubdomain = $this->registrableGuess($host);

        $candidates = DB::table('retailers')
            ->select(['id', 'name', 'base_url', 'logo_path', 'is_active'])
            ->whereNull('deleted_at')
            ->where(function ($query) use ($host, $hostWithoutSubdomain) {
                $query
                    ->where('base_url', $host)
                    ->orWhere('base_url', 'www.' . $host)
                    ->orWhere('base_url', 'like', '%' . $host . '%');

                if ($hostWithoutSubdomain && $hostWithoutSubdomain !== $host) {
                    $query
                        ->orWhere('base_url', $hostWithoutSubdomain)
                        ->orWhere('base_url', 'www.' . $hostWithoutSubdomain)
                        ->orWhere('base_url', 'like', '%' . $hostWithoutSubdomain . '%');
                }
            })
            ->where('is_active', 1)
            ->limit(20)
            ->get();

        foreach ($candidates as $candidate) {
            $candidateHost = $this->hostFromUrl((string) $candidate->base_url);

            if ($candidateHost === '') {
                $candidateHost = $this->cleanHost((string) $candidate->base_url);
            }

            if ($candidateHost === '') {
                continue;
            }

            if (
                $candidateHost === $host
                || str_ends_with($host, '.' . $candidateHost)
                || str_ends_with($candidateHost, '.' . $host)
                || ($hostWithoutSubdomain && $candidateHost === $hostWithoutSubdomain)
            ) {
                return $candidate;
            }
        }

        return null;
    }

    private function findByName(string $name): ?object
    {
        $name = trim($name);

        if ($name === '') {
            return null;
        }

        return DB::table('retailers')
            ->select(['id', 'name', 'base_url', 'logo_path', 'is_active'])
            ->whereNull('deleted_at')
            ->where('is_active', 1)
            ->where(function ($query) use ($name) {
                $query
                    ->where('name', $name)
                    ->orWhere('name', 'like', '%' . $name . '%');
            })
            ->orderByRaw('CASE WHEN name = ? THEN 0 ELSE 1 END', [$name])
            ->first();
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

        $host = parse_url($url, PHP_URL_HOST);

        if (! $host) {
            $host = $url;
        }

        return $this->cleanHost((string) $host);
    }

    private function cleanHost(string $host): string
    {
        $host = strtolower(trim($host));
        $host = preg_replace('/^https?:\/\//', '', $host);
        $host = explode('/', $host)[0] ?? $host;

        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }

        return $host;
    }

    private function registrableGuess(string $host): string
    {
        $parts = explode('.', $host);

        if (count($parts) <= 2) {
            return $host;
        }

        $lastTwo = array_slice($parts, -2);

        if (in_array($lastTwo[0] . '.' . $lastTwo[1], ['co.uk', 'com.au', 'co.nz'], true) && count($parts) >= 3) {
            return implode('.', array_slice($parts, -3));
        }

        return implode('.', $lastTwo);
    }

    private function guessReadableName(string $url): string
    {
        $host = $this->hostFromUrl($url);

        if ($host === '') {
            return 'Unknown retailer';
        }

        $main = explode('.', $host)[0] ?? $host;

        return Str::title(str_replace(['-', '_'], ' ', $main));
    }
}
