<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;

class GlobalFeeSettingsService
{
    public function all()
    {
        return DB::table('global_fees')->orderByDesc('id')->get();
    }

    public function active(): ?object
    {
        return DB::table('global_fees')->where('is_active', 1)->orderByDesc('id')->first();
    }

    public function create(float $ratePercent, float $minimumFee, int $userId): int
    {
        return DB::transaction(function () use ($ratePercent, $minimumFee, $userId): int {
            DB::table('global_fees')->update(['is_active' => 0, 'updated_at' => now()]);

            $id = DB::table('global_fees')->insertGetId([
                'dabba_fee_rate' => round(max(0, $ratePercent) / 100, 4),
                'dabba_fee_min' => round(max(0, $minimumFee), 2),
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->log($id, 'Global Dabba fee updated', 'Global Dabba fee changed to ' . number_format($ratePercent, 2) . '%, minimum £' . number_format($minimumFee, 2) . '.', $userId);

            return (int) $id;
        });
    }

    private function log(int $feeId, string $title, string $body, int $userId): void
    {
        if (! DB::getSchemaBuilder()->hasTable('activity_logs')) {
            return;
        }

        DB::table('activity_logs')->insert([
            'subject_type' => 'global_fee',
            'subject_id' => $feeId,
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
