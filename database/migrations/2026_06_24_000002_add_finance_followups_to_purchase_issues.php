<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_issues', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_issues', 'finance_action_required')) {
                $table->boolean('finance_action_required')->default(false)->after('resolution_type');
            }

            if (! Schema::hasColumn('purchase_issues', 'finance_actions')) {
                $table->json('finance_actions')->nullable()->after('finance_action_required');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_issues', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_issues', 'finance_actions')) {
                $table->dropColumn('finance_actions');
            }

            if (Schema::hasColumn('purchase_issues', 'finance_action_required')) {
                $table->dropColumn('finance_action_required');
            }
        });
    }
};
