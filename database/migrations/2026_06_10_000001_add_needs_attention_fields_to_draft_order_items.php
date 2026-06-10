<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('draft_order_items')) {
            return;
        }

        Schema::table('draft_order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('draft_order_items', 'needs_attention_at')) {
                $table->timestamp('needs_attention_at')->nullable();
            }

            if (! Schema::hasColumn('draft_order_items', 'needs_attention_by_user_id')) {
                $table->unsignedBigInteger('needs_attention_by_user_id')->nullable();
            }

            if (! Schema::hasColumn('draft_order_items', 'needs_attention_note')) {
                $table->string('needs_attention_note', 255)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('draft_order_items')) {
            return;
        }

        Schema::table('draft_order_items', function (Blueprint $table) {
            if (Schema::hasColumn('draft_order_items', 'needs_attention_note')) {
                $table->dropColumn('needs_attention_note');
            }

            if (Schema::hasColumn('draft_order_items', 'needs_attention_by_user_id')) {
                $table->dropColumn('needs_attention_by_user_id');
            }

            if (Schema::hasColumn('draft_order_items', 'needs_attention_at')) {
                $table->dropColumn('needs_attention_at');
            }
        });
    }
};
