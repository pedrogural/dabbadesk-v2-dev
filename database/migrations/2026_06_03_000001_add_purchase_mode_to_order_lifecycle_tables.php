<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['order_requests', 'draft_orders', 'orders'] as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'purchase_mode')) {
                Schema::table($table, function (Blueprint $table): void {
                    $table->string('purchase_mode', 40)->default('standard')->after('status')->index();
                });
            }
        }

        foreach (['order_requests', 'draft_orders', 'orders'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'purchase_mode')) {
                DB::table($table)->whereNull('purchase_mode')->orWhere('purchase_mode', '')->update(['purchase_mode' => 'standard']);
            }
        }
    }

    public function down(): void
    {
        foreach (['orders', 'draft_orders', 'order_requests'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'purchase_mode')) {
                Schema::table($table, function (Blueprint $table): void {
                    $table->dropColumn('purchase_mode');
                });
            }
        }
    }
};
