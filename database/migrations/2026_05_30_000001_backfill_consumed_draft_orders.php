<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('draft_orders') || ! Schema::hasTable('orders')) {
            return;
        }

        DB::statement(<<<'SQL'
            UPDATE draft_orders d
            JOIN (
                SELECT draft_id, MAX(order_id) AS order_id
                FROM (
                    SELECT draft_order_id AS draft_id, id AS order_id
                    FROM orders
                    WHERE draft_order_id IS NOT NULL
                    UNION ALL
                    SELECT source_draft_order_id AS draft_id, id AS order_id
                    FROM orders
                    WHERE source_draft_order_id IS NOT NULL
                ) child_orders
                WHERE draft_id IS NOT NULL
                GROUP BY draft_id
            ) child ON child.draft_id = d.id
            SET
                d.finalized_order_id = COALESCE(d.finalized_order_id, child.order_id),
                d.status = 'consumed',
                d.state = 'consumed',
                d.updated_at = COALESCE(d.updated_at, NOW())
            WHERE d.finalized_order_id IS NULL
               OR d.status IN ('open', 'ready', 'reviewing', 'finalised')
               OR d.state IN ('draft', 'finalised')
        SQL);

        DB::table('draft_orders')
            ->where('status', 'finalised')
            ->update(['status' => 'consumed', 'state' => 'consumed', 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Intentional data migration; do not automatically undo consumed lifecycle backfill.
    }
};
