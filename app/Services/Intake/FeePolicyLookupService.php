<?php

namespace App\Services\Intake;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FeePolicyLookupService
{
    public function activePolicy(): array
    {
        return $this->normalisePolicy($this->fromGlobalFees() ?? $this->fallbackPolicy());
    }

    public function policyForCustomer(?int $customerId): array
    {
        $global = $this->activePolicy();

        if (! $customerId || ! Schema::hasTable('customers')) {
            return $global;
        }

        $customer = DB::table('customers')->where('id', $customerId)->first();
        if (! $customer) {
            return $global;
        }

        if ((int) ($customer->dabba_fee_is_disabled ?? 0) === 1) {
            return $this->normalisePolicy([
                'id' => $global['id'] ?? null,
                'name' => 'Customer fee disabled',
                'currency' => 'GBP',
                'minimum_fee' => 0,
                'percentage_rate' => 0,
                'calculation_basis' => 'retailer_subtotal',
                'rounding' => 'money_2dp',
                'source' => 'customer_disabled',
                'level' => 'disabled',
                'fee_mode' => 'fee_disabled',
            ]);
        }

        $level = (string) ($customer->dabba_fee_level ?? 'global');
        $customerRate = $customer->dabba_fee_rate ?? null;
        $customerMin = $customer->dabba_fee_min ?? null;

        if (in_array($level, ['custom', 'customer'], true) && $customerRate !== null && $customerMin !== null) {
            return $this->normalisePolicy([
                'id' => null,
                'name' => 'Customer-specific Dabba fee',
                'currency' => 'GBP',
                'minimum_fee' => $customerMin,
                'percentage_rate' => $customerRate,
                'calculation_basis' => 'retailer_subtotal',
                'rounding' => 'money_2dp',
                'source' => 'customer',
                'level' => 'customer',
                'fee_mode' => 'standard',
            ]);
        }

        $global['level'] = 'global';
        $global['fee_mode'] = 'standard';

        return $global;
    }

    public function activeGlobalRow(): ?object
    {
        if (! $this->tableExists('global_fees')) {
            return null;
        }

        return DB::table('global_fees')
            ->where('is_active', 1)
            ->orderByDesc('id')
            ->first();
    }

    private function fromGlobalFees(): ?array
    {
        $row = $this->activeGlobalRow();
        if (! $row) {
            return null;
        }

        return [
            'id' => $row->id ?? null,
            'name' => 'Active global Dabba fee',
            'currency' => 'GBP',
            'minimum_fee' => $row->dabba_fee_min ?? 10,
            'percentage_rate' => $row->dabba_fee_rate ?? 0.20,
            'calculation_basis' => 'retailer_subtotal',
            'rounding' => 'money_2dp',
            'source' => 'global_fees',
            'level' => 'global',
            'fee_mode' => 'standard',
        ];
    }

    private function fallbackPolicy(): array
    {
        return [
            'id' => null,
            'name' => 'Standard Dabba fee fallback',
            'currency' => 'GBP',
            'minimum_fee' => 10,
            'percentage_rate' => 0.20,
            'calculation_basis' => 'retailer_subtotal',
            'rounding' => 'money_2dp',
            'source' => 'fallback',
            'level' => 'global',
            'fee_mode' => 'standard',
        ];
    }

    private function normalisePolicy(array $policy): array
    {
        $rateFraction = $this->rateAsFraction((float) ($policy['percentage_rate'] ?? 0.20));
        $minimumFee = round((float) ($policy['minimum_fee'] ?? 10), 2);
        $currency = (string) ($policy['currency'] ?? 'GBP');

        return [
            'id' => $policy['id'] ?? null,
            'name' => $policy['name'] ?? 'Dabba fee policy',
            'currency' => $currency,
            'minimum_fee' => $minimumFee,
            'percentage_rate' => $rateFraction,
            'percentage_rate_percent' => round($rateFraction * 100, 4),
            'calculation_basis' => $policy['calculation_basis'] ?? 'retailer_subtotal',
            'rounding' => $policy['rounding'] ?? 'money_2dp',
            'formula_label' => $this->formulaLabel($minimumFee, $rateFraction, $currency),
            'source' => $policy['source'] ?? 'unknown',
            'level' => $policy['level'] ?? ($policy['source'] ?? 'global'),
            'fee_mode' => $policy['fee_mode'] ?? 'standard',
        ];
    }

    public function rateAsFraction(float $rate): float
    {
        return round($rate > 1 ? $rate / 100 : $rate, 6);
    }

    public function rateAsPercent(float $rate): float
    {
        return round(($rate > 1 ? $rate : $rate * 100), 4);
    }

    private function formulaLabel(float $minimumFee, float $percentageRate, string $currency): string
    {
        $symbol = match (strtoupper($currency)) {
            'GBP' => '£',
            'EUR' => '€',
            default => strtoupper($currency) . ' ',
        };

        return 'Per retailer: max(' . $symbol . number_format($minimumFee, 2) . ', ' . rtrim(rtrim(number_format($percentageRate * 100, 2), '0'), '.') . '% of retailer subtotal)';
    }

    private function tableExists(string $table): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }
}
