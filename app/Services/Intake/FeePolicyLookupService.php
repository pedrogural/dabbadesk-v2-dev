<?php

namespace App\Services\Intake;

use Illuminate\Support\Facades\DB;

class FeePolicyLookupService
{
    public function activePolicy(): array
    {
        /*
         * Source of truth in the current CMS schema:
         *
         * global_fees
         * - id
         * - dabba_fee_rate
         * - dabba_fee_min
         * - is_active
         * - created_at
         * - updated_at
         *
         * Fee rule:
         * Per retailer: max(dabba_fee_min, dabba_fee_rate * retailer subtotal)
         */

        $policy = $this->fromGlobalFees()
            ?? $this->fallbackPolicy();

        return [
            'id' => $policy['id'],
            'name' => $policy['name'],
            'currency' => $policy['currency'],
            'minimum_fee' => (float) $policy['minimum_fee'],
            'percentage_rate' => (float) $policy['percentage_rate'],
            'calculation_basis' => $policy['calculation_basis'],
            'rounding' => $policy['rounding'],
            'formula_label' => $this->formulaLabel(
                minimumFee: (float) $policy['minimum_fee'],
                percentageRate: (float) $policy['percentage_rate'],
                currency: $policy['currency'],
            ),
            'source' => $policy['source'],
        ];
    }

    private function fromGlobalFees(): ?array
    {
        if (! $this->tableExists('global_fees')) {
            return null;
        }

        $row = DB::table('global_fees')
            ->where('is_active', 1)
            ->orderByDesc('id')
            ->first();

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
        ];
    }

    private function formulaLabel(float $minimumFee, float $percentageRate, string $currency): string
    {
        $symbol = match (strtoupper($currency)) {
            'GBP' => '£',
            'EUR' => '€',
            default => strtoupper($currency) . ' ',
        };

        return 'Per retailer: max('
            . $symbol
            . number_format($minimumFee, 2)
            . ', '
            . rtrim(rtrim(number_format($percentageRate * 100, 2), '0'), '.')
            . '% of retailer subtotal)';
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
