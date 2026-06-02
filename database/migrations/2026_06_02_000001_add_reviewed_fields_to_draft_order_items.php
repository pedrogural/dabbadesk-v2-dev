<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('draft_order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('draft_order_items', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('item_delivery_fee');
            }

            if (! Schema::hasColumn('draft_order_items', 'reviewed_by_user_id')) {
                $table->foreignId('reviewed_by_user_id')
                    ->nullable()
                    ->after('reviewed_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('draft_order_items', function (Blueprint $table) {
            if (Schema::hasColumn('draft_order_items', 'reviewed_by_user_id')) {
                $table->dropConstrainedForeignId('reviewed_by_user_id');
            }

            if (Schema::hasColumn('draft_order_items', 'reviewed_at')) {
                $table->dropColumn('reviewed_at');
            }
        });
    }
};
