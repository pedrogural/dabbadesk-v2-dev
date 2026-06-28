<?php

namespace App\Services\Retailers;

use DabbaDirect\IntakeTools\HostRetailerGuesser;
use DabbaDirect\IntakeTools\UrlTools;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RetailerRegistrationService
{
    public function __construct(
        private readonly UrlTools $urlTools,
        private readonly HostRetailerGuesser $hostGuesser,
    ) {}

    /**
     * Register or reactivate a retailer using Dabba's shared URL normalisation tools.
     *
     * This service intentionally lives in DabbaDesk rather than dabba-shared so the
     * purchasing UI can enforce CMS database rules without changing public intake
     * or draft-order behaviour.
     */
    public function register(array $data, ?int $userId = null): array
    {
        $name = $this->normaliseName((string) ($data['name'] ?? ''));
        $rawUrl = trim((string) ($data['base_url'] ?? $data['url'] ?? ''));

        if ($rawUrl === '') {
            throw ValidationException::withMessages([
                'base_url' => 'Website / base URL is required.',
            ]);
        }

        $host = $this->normalisedHost($rawUrl);
        $this->validateHost($host);

        if ($name === '') {
            $name = $this->hostGuesser->guess($host) ?: $this->friendlyNameFromHost($host);
        }

        if (mb_strlen($name) < 2) {
            throw ValidationException::withMessages([
                'name' => 'Supplier / retailer name must be at least 2 characters.',
            ]);
        }

        if (mb_strlen($name) > 191) {
            throw ValidationException::withMessages([
                'name' => 'Supplier / retailer name must be 191 characters or fewer.',
            ]);
        }

        if (mb_strlen($host) > 191) {
            throw ValidationException::withMessages([
                'base_url' => 'The website host is too long for the retailer table. Please use the main website address only.',
            ]);
        }

        $existing = $this->findExistingByHost($host);

        if ($existing !== null) {
            $this->reactivateExisting((int) $existing->id, $userId);

            return [
                'id' => (int) $existing->id,
                'name' => (string) $existing->name,
                'base_url' => (string) $existing->base_url,
                'normalised_base_url' => $host,
                'already_exists' => true,
                'reactivated' => $this->isInactive($existing),
            ];
        }

        $insert = [
            'name' => $name,
            'base_url' => $host,
        ];

        if (Schema::hasColumn('retailers', 'is_active')) {
            $insert['is_active'] = 1;
        }
        if (Schema::hasColumn('retailers', 'active')) {
            $insert['active'] = 1;
        }
        if (Schema::hasColumn('retailers', 'code')) {
            $insert['code'] = Str::slug($name) ?: Str::slug($host);
        }
        if (Schema::hasColumn('retailers', 'retailer_code')) {
            $insert['retailer_code'] = Str::slug($name) ?: Str::slug($host);
        }
        if (Schema::hasColumn('retailers', 'created_by_user_id')) {
            $insert['created_by_user_id'] = $userId;
        }
        if (Schema::hasColumn('retailers', 'updated_by_user_id')) {
            $insert['updated_by_user_id'] = $userId;
        }
        if (Schema::hasColumn('retailers', 'created_at')) {
            $insert['created_at'] = now();
        }
        if (Schema::hasColumn('retailers', 'updated_at')) {
            $insert['updated_at'] = now();
        }

        $id = (int) DB::table('retailers')->insertGetId($insert);

        return [
            'id' => $id,
            'name' => $name,
            'base_url' => $host,
            'normalised_base_url' => $host,
            'already_exists' => false,
            'reactivated' => false,
        ];
    }

    private function normaliseName(string $name): string
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?: $name);

        return $name;
    }

    private function normalisedHost(string $rawUrl): string
    {
        // Use the shared intake URL tools. They add https:// where missing, strip
        // protocol, paths, query strings, ports, leading www and trailing slashes.
        return $this->urlTools->hostFromUrl($rawUrl);
    }

    private function validateHost(string $host): void
    {
        if ($host === '') {
            throw ValidationException::withMessages([
                'base_url' => 'Please enter a valid website, for example argos.co.uk.',
            ]);
        }

        if (str_contains($host, ' ') || ! preg_match('/^[a-z0-9.-]+$/i', $host)) {
            throw ValidationException::withMessages([
                'base_url' => 'Please enter the main website address only, for example argos.co.uk.',
            ]);
        }

        if (! str_contains($host, '.')) {
            throw ValidationException::withMessages([
                'base_url' => 'Please enter a full website host, for example argos.co.uk.',
            ]);
        }
    }

    private function findExistingByHost(string $host): ?object
    {
        $candidates = $this->urlTools->hostCandidates($host);

        $query = DB::table('retailers')
            ->select(array_values(array_filter([
                'id',
                'name',
                'base_url',
                Schema::hasColumn('retailers', 'is_active') ? 'is_active' : null,
                Schema::hasColumn('retailers', 'active') ? 'active' : null,
                Schema::hasColumn('retailers', 'deleted_at') ? 'deleted_at' : null,
            ])));

        if (Schema::hasColumn('retailers', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        $exact = (clone $query)
            ->whereIn(DB::raw('LOWER(TRIM(base_url))'), array_map('strtolower', $candidates))
            ->first();

        if ($exact !== null) {
            return $exact;
        }

        // Some older rows may be stored as https://www.example.com/ rather than
        // example.com. Scan the compact retailer table and compare using shared
        // normalisation so we avoid duplicate www/protocol variants.
        foreach ($query->get() as $retailer) {
            $existingHost = $this->urlTools->normaliseHost((string) ($retailer->base_url ?? ''));
            if ($existingHost === '') {
                continue;
            }

            if (in_array($existingHost, $candidates, true)) {
                return $retailer;
            }
        }

        return null;
    }

    private function reactivateExisting(int $retailerId, ?int $userId): void
    {
        $update = [];

        if (Schema::hasColumn('retailers', 'is_active')) {
            $update['is_active'] = 1;
        }
        if (Schema::hasColumn('retailers', 'active')) {
            $update['active'] = 1;
        }
        if (Schema::hasColumn('retailers', 'updated_by_user_id')) {
            $update['updated_by_user_id'] = $userId;
        }
        if (Schema::hasColumn('retailers', 'updated_at')) {
            $update['updated_at'] = now();
        }

        if ($update !== []) {
            DB::table('retailers')->where('id', $retailerId)->update($update);
        }
    }

    private function isInactive(object $retailer): bool
    {
        if (property_exists($retailer, 'is_active')) {
            return ! (bool) $retailer->is_active;
        }

        if (property_exists($retailer, 'active')) {
            return ! (bool) $retailer->active;
        }

        return false;
    }

    private function friendlyNameFromHost(string $host): string
    {
        $registrable = $this->urlTools->registrableGuess($host);
        $first = explode('.', $registrable)[0] ?? $host;
        $first = str_replace(['-', '_'], ' ', $first);

        return trim(mb_convert_case($first, MB_CASE_TITLE, 'UTF-8'));
    }
}
