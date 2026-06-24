<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('purchase_issues')) {
            return;
        }

        Schema::table('purchase_issues', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_issues', 'issue_stage')) {
                $table->string('issue_stage', 30)->default('pre_purchase')->after('issue_type')->index();
            }

            if (! Schema::hasColumn('purchase_issues', 'arrival_expectation')) {
                $table->string('arrival_expectation', 30)->default('expected')->after('issue_stage')->index();
            }

            if (! Schema::hasColumn('purchase_issues', 'affected_qty')) {
                $table->unsignedInteger('affected_qty')->nullable()->after('qty');
            }
        });

        DB::table('purchase_issues')
            ->whereNull('affected_qty')
            ->update(['affected_qty' => DB::raw('qty')]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('purchase_issues')) {
            return;
        }

        Schema::table('purchase_issues', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_issues', 'arrival_expectation')) {
                $table->dropColumn('arrival_expectation');
            }

            if (Schema::hasColumn('purchase_issues', 'issue_stage')) {
                $table->dropColumn('issue_stage');
            }

            if (Schema::hasColumn('purchase_issues', 'affected_qty')) {
                $table->dropColumn('affected_qty');
            }
        });
    }
};
