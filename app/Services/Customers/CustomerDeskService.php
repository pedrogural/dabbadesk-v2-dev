<?php

namespace App\Services\Customers;

use App\Services\Intake\FeePolicyLookupService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CustomerDeskService
{
    public function search(array $filters): LengthAwarePaginator
    {
        $query = $this->baseSearchQuery();
        $this->applySearch($query, trim((string) ($filters['q'] ?? '')));

        return $query->paginate(25)->withQueryString();
    }

    public function liveSearch(string $q = '', int $limit = 25): array
    {
        $query = $this->baseSearchQuery();
        $this->applySearch($query, trim($q));

        return $query->limit(max(1, min(50, $limit)))->get()->map(fn ($customer) => $this->normaliseSearchRow($customer))->all();
    }

    private function baseSearchQuery()
    {
        return DB::table('customers as c')
            ->select('c.*')
            ->selectRaw('(select e.email from customer_emails ce join emails e on e.id = ce.email_id where ce.customer_id = c.id and ce.is_active = 1 order by ce.is_primary desc, ce.id asc limit 1) as primary_email')
            ->selectRaw('(select p.phone from customer_phones cp join phones p on p.id = cp.phone_id where cp.customer_id = c.id and cp.is_active = 1 order by cp.is_primary desc, cp.id asc limit 1) as primary_phone')
            ->orderByDesc('c.updated_at')
            ->orderByDesc('c.id');
    }

    private function applySearch($query, string $q): void
    {
        if ($q === '') {
            return;
        }

        $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
        $digits = preg_replace('/\D+/', '', $q) ?: '';
        $nameParts = collect(preg_split('/\s+/', $q))->filter()->values();

        $query->where(function ($inner) use ($like, $digits, $nameParts) {
            $inner->where('c.first_name', 'like', $like)
                ->orWhere('c.last_name', 'like', $like)
                ->orWhere('c.company_name', 'like', $like)
                ->orWhere('c.reference', 'like', $like)
                ->orWhereRaw("concat_ws(' ', c.first_name, c.last_name) like ?", [$like])
                ->orWhereExists(fn ($sub) => $sub->selectRaw('1')
                    ->from('customer_emails as ce')
                    ->join('emails as e', 'e.id', '=', 'ce.email_id')
                    ->whereColumn('ce.customer_id', 'c.id')
                    ->where('e.email', 'like', $like))
                ->orWhereExists(fn ($sub) => $sub->selectRaw('1')
                    ->from('customer_phones as cp')
                    ->join('phones as p', 'p.id', '=', 'cp.phone_id')
                    ->whereColumn('cp.customer_id', 'c.id')
                    ->where(function ($phone) use ($like, $digits) {
                        $phone->where('p.phone', 'like', $like);
                        if ($digits !== '') {
                            $phone->orWhereRaw("replace(replace(replace(replace(replace(p.phone, '+', ''), ' ', ''), '-', ''), '(', ''), ')', '') like ?", ['%' . $digits . '%']);
                        }
                    }));

            if ($nameParts->count() >= 2) {
                $first = '%' . str_replace(['%', '_'], ['\\%', '\\_'], (string) $nameParts->first()) . '%';
                $last = '%' . str_replace(['%', '_'], ['\\%', '\\_'], (string) $nameParts->last()) . '%';
                $inner->orWhere(fn ($names) => $names->where('c.first_name', 'like', $first)->where('c.last_name', 'like', $last));
            }
        });
    }

    private function normaliseSearchRow(object $customer): array
    {
        $name = trim((string) (($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')));
        if ($name === '') {
            $name = $customer->company_name ?: 'Unknown customer';
        }

        return [
            'id' => (int) $customer->id,
            'name' => $name,
            'company_name' => $customer->company_name,
            'reference' => $customer->reference,
            'email' => $customer->primary_email ?: '',
            'phone' => $customer->primary_phone ?: '',
            'fee_label' => $customer->dabba_fee_is_disabled ? 'Fee disabled' : ucfirst((string) ($customer->dabba_fee_level ?: 'global')),
            'is_active' => (bool) $customer->is_active,
            'url' => route('customers.show', $customer->id),
            'edit_url' => route('customers.edit', $customer->id),
        ];
    }

    public function find(int $customerId): ?object
    {
        return DB::table('customers')->where('id', $customerId)->first();
    }

    public function details(int $customerId): array
    {
        $email = DB::table('customer_emails as ce')
            ->join('emails as e', 'e.id', '=', 'ce.email_id')
            ->where('ce.customer_id', $customerId)
            ->where('ce.is_active', 1)
            ->orderByDesc('ce.is_primary')
            ->select('e.email')
            ->first();

        $phone = DB::table('customer_phones as cp')
            ->join('phones as p', 'p.id', '=', 'cp.phone_id')
            ->where('cp.customer_id', $customerId)
            ->where('cp.is_active', 1)
            ->orderByDesc('cp.is_primary')
            ->select('p.phone', 'p.country_id')
            ->first();

        $address = DB::table('customer_addresses as ca')
            ->join('addresses as a', 'a.id', '=', 'ca.address_id')
            ->leftJoin('countries as c', 'c.id', '=', 'a.country_id')
            ->where('ca.customer_id', $customerId)
            ->where('ca.is_active', 1)
            ->orderByDesc('ca.is_primary')
            ->select('a.*', 'c.name as country_name')
            ->first();

        return [
            'email' => $email->email ?? '',
            'phone' => $phone->phone ?? '',
            'phone_country_id' => $phone->country_id ?? null,
            'address' => $address,
        ];
    }

    public function countries()
    {
        if (! Schema::hasTable('countries')) {
            return collect();
        }

        return DB::table('countries')
            ->where('is_active', 1)
            ->select('id', 'name', 'iso2', 'phone_code')
            ->orderByRaw("case when name = 'Gibraltar' then 0 when name = 'Spain' then 1 when name in ('United Kingdom', 'UK') then 2 else 3 end")
            ->orderBy('name')
            ->get();
    }

    public function formDefaults(): array
    {
        $gibraltar = Schema::hasTable('countries')
            ? DB::table('countries')->where('name', 'Gibraltar')->orWhere('iso2', 'GI')->first()
            : null;

        return [
            'country_id' => $gibraltar->id ?? null,
            'phone_country_id' => $gibraltar->id ?? null,
            'postcode' => 'GX11 1AA',
        ];
    }

    public function effectiveFeePolicy(int $customerId): array
    {
        return app(FeePolicyLookupService::class)->policyForCustomer($customerId);
    }

    public function create(array $data, int $userId): int
    {
        return DB::transaction(function () use ($data, $userId): int {
            $customerId = DB::table('customers')->insertGetId([
                'first_name' => $this->titleName($data['first_name'] ?? ''),
                'last_name' => $this->titleName($data['last_name'] ?? ''),
                'company_name' => $this->blankToNull($data['company_name'] ?? null),
                'customer_type' => ! empty($data['company_name']) && empty($data['first_name']) ? 'company' : 'individual',
                'is_active' => ! empty($data['is_active']) ? 1 : 0,
                'reference' => $this->blankToNull($data['reference'] ?? null),
                'dabba_fee_level' => $this->feeLevel($data),
                'dabba_fee_rate' => $this->feeRateFraction($data),
                'dabba_fee_min' => $this->feeMin($data),
                'dabba_fee_is_disabled' => ! empty($data['dabba_fee_is_disabled']) ? 1 : 0,
                'created_by_user_id' => $userId,
                'updated_by_user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->attachEmail($customerId, (string) ($data['email'] ?? ''), $userId);
            $this->attachPhone($customerId, (string) ($data['phone'] ?? ''), ! empty($data['phone_country_id']) ? (int) $data['phone_country_id'] : null, $userId);
            $this->attachPrimaryAddress($customerId, $data, $userId, 'Primary');
            $this->log($customerId, 'Customer created', 'Customer record was created.', $userId);

            return (int) $customerId;
        });
    }

    public function update(int $customerId, array $data, int $userId): void
    {
        DB::transaction(function () use ($customerId, $data, $userId): void {
            DB::table('customers')->where('id', $customerId)->update([
                'first_name' => $this->titleName($data['first_name'] ?? ''),
                'last_name' => $this->titleName($data['last_name'] ?? ''),
                'company_name' => $this->blankToNull($data['company_name'] ?? null),
                'customer_type' => ! empty($data['company_name']) && empty($data['first_name']) ? 'company' : 'individual',
                'is_active' => ! empty($data['is_active']) ? 1 : 0,
                'reference' => $this->blankToNull($data['reference'] ?? null),
                'dabba_fee_level' => $this->feeLevel($data),
                'dabba_fee_rate' => $this->feeRateFraction($data),
                'dabba_fee_min' => $this->feeMin($data),
                'dabba_fee_is_disabled' => ! empty($data['dabba_fee_is_disabled']) ? 1 : 0,
                'updated_by_user_id' => $userId,
                'updated_at' => now(),
            ]);

            $this->attachEmail($customerId, (string) ($data['email'] ?? ''), $userId);
            $this->attachPhone($customerId, (string) ($data['phone'] ?? ''), ! empty($data['phone_country_id']) ? (int) $data['phone_country_id'] : null, $userId);
            $this->attachPrimaryAddress($customerId, $data, $userId, 'Primary');
            $this->log($customerId, 'Customer updated', 'Customer details or fee settings were updated.', $userId);
        });
    }

    private function titleName(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return collect(preg_split('/(\s+)/u', mb_strtolower($value), -1, PREG_SPLIT_DELIM_CAPTURE))
            ->map(function ($part) {
                if (trim($part) === '') {
                    return $part;
                }

                return collect(preg_split('/([\-\'’])/u', $part, -1, PREG_SPLIT_DELIM_CAPTURE))
                    ->map(fn ($piece) => in_array($piece, ['-', "'", '’'], true) ? $piece : Str::ucfirst($piece))
                    ->implode('');
            })
            ->implode('');
    }

    private function blankToNull(?string $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    private function feeLevel(array $data): string
    {
        if (! empty($data['dabba_fee_is_disabled'])) {
            return 'disabled';
        }

        $level = (string) ($data['dabba_fee_level'] ?? 'global');
        return in_array($level, ['global', 'custom'], true) ? $level : 'global';
    }

    private function feeRateFraction(array $data): ?float
    {
        if (($data['dabba_fee_level'] ?? 'global') !== 'custom' || ! empty($data['dabba_fee_is_disabled'])) {
            return null;
        }

        return round(max(0, (float) ($data['dabba_fee_rate'] ?? 0)) / 100, 4);
    }

    private function feeMin(array $data): ?float
    {
        if (($data['dabba_fee_level'] ?? 'global') !== 'custom' || ! empty($data['dabba_fee_is_disabled'])) {
            return null;
        }

        return round(max(0, (float) ($data['dabba_fee_min'] ?? 0)), 2);
    }

    private function attachEmail(int $customerId, string $email, int $userId): void
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return;
        }

        $emailId = DB::table('emails')->where('email', $email)->value('id') ?: DB::table('emails')->insertGetId([
            'email' => $email,
            'is_active' => 1,
            'created_by_user_id' => $userId,
            'updated_by_user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('customer_emails')->where('customer_id', $customerId)->update(['is_primary' => 0, 'updated_by_user_id' => $userId, 'updated_at' => now()]);
        DB::table('customer_emails')->updateOrInsert(
            ['customer_id' => $customerId, 'email_id' => $emailId],
            ['is_primary' => 1, 'is_active' => 1, 'created_by_user_id' => $userId, 'updated_by_user_id' => $userId, 'created_at' => now(), 'updated_at' => now()]
        );
    }

    private function attachPhone(int $customerId, string $phone, ?int $countryId, int $userId): void
    {
        $phone = $this->normalisePhone($phone, $countryId);
        if ($phone === '') {
            return;
        }

        $phoneId = DB::table('phones')->where('phone', $phone)->where('country_id', $countryId)->value('id') ?: DB::table('phones')->insertGetId([
            'phone' => $phone,
            'country_id' => $countryId,
            'is_active' => 1,
            'created_by_user_id' => $userId,
            'updated_by_user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('customer_phones')->where('customer_id', $customerId)->update(['is_primary' => 0, 'updated_by_user_id' => $userId, 'updated_at' => now()]);
        DB::table('customer_phones')->updateOrInsert(
            ['customer_id' => $customerId, 'phone_id' => $phoneId],
            ['is_primary' => 1, 'is_active' => 1, 'created_by_user_id' => $userId, 'updated_by_user_id' => $userId, 'created_at' => now(), 'updated_at' => now()]
        );
    }

    private function normalisePhone(string $phone, ?int $countryId): string
    {
        $raw = trim($phone);
        $digits = preg_replace('/\D+/', '', $raw) ?: '';
        if ($digits === '') {
            return '';
        }

        if (str_starts_with($raw, '+')) {
            return '+' . $digits;
        }

        $code = null;
        if ($countryId && Schema::hasTable('countries')) {
            $code = DB::table('countries')->where('id', $countryId)->value('phone_code');
            $code = preg_replace('/\D+/', '', (string) $code) ?: null;
        }

        return $code ? '+' . $code . $digits : $digits;
    }

    private function attachPrimaryAddress(int $customerId, array $data, int $userId, string $label): void
    {
        $line1 = trim((string) ($data['line1'] ?? ''));
        if ($line1 === '') {
            return;
        }

        $payload = [
            'line1' => $this->titleAddress($line1),
            'line2' => $this->blankToNull($this->titleAddress((string) ($data['line2'] ?? ''))),
            'city' => $this->blankToNull($this->titleAddress((string) ($data['city'] ?? ''))),
            'region' => $this->blankToNull($this->titleAddress((string) ($data['region'] ?? ''))),
            'postcode' => strtoupper(trim((string) ($data['postcode'] ?? ''))) ?: null,
            'country_id' => ! empty($data['country_id']) ? (int) $data['country_id'] : null,
            'is_active' => 1,
            'updated_by_user_id' => $userId,
            'updated_at' => now(),
        ];

        $existing = DB::table('customer_addresses as ca')
            ->join('addresses as a', 'a.id', '=', 'ca.address_id')
            ->where('ca.customer_id', $customerId)
            ->where('ca.is_primary', 1)
            ->where('ca.is_active', 1)
            ->select('a.id')
            ->first();

        if ($existing) {
            DB::table('addresses')->where('id', $existing->id)->update($payload);
            DB::table('customer_addresses')->where('customer_id', $customerId)->where('address_id', $existing->id)->update([
                'label' => $label,
                'updated_by_user_id' => $userId,
                'updated_at' => now(),
            ]);
            return;
        }

        $addressId = DB::table('addresses')->insertGetId($payload + [
            'created_by_user_id' => $userId,
            'created_at' => now(),
        ]);

        DB::table('customer_addresses')->where('customer_id', $customerId)->update(['is_primary' => 0, 'updated_by_user_id' => $userId, 'updated_at' => now()]);
        DB::table('customer_addresses')->insert([
            'customer_id' => $customerId,
            'address_id' => $addressId,
            'is_primary' => 1,
            'is_active' => 1,
            'label' => $label,
            'created_by_user_id' => $userId,
            'updated_by_user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function titleAddress(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        return Str::title($value);
    }

    private function log(int $customerId, string $title, string $body, int $userId): void
    {
        if (! Schema::hasTable('activity_logs')) {
            return;
        }

        DB::table('activity_logs')->insert([
            'subject_type' => 'customer',
            'subject_id' => $customerId,
            'type' => 'system_note',
            'is_pinned' => 0,
            'title' => $title,
            'body' => $body,
            'occurred_at' => now(),
            'created_by_user_id' => $userId,
            'updated_by_user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
