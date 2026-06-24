<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('purchase_issues')) {
            return;
        }

        Schema::create('purchase_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete()->cascadeOnUpdate();
            $table->unsignedBigInteger('root_item_id')->index();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete()->cascadeOnUpdate();
            $table->unsignedBigInteger('order_retailer_id')->nullable()->index();
            $table->unsignedBigInteger('retailer_id')->nullable()->index();
            $table->unsignedInteger('qty')->default(1);
            $table->string('issue_type', 50);
            $table->string('severity', 20)->default('medium');
            $table->string('status', 30)->default('open');
            $table->text('notes')->nullable();
            $table->boolean('requires_customer_action')->default(false);
            $table->timestamp('customer_contacted_at')->nullable();
            $table->timestamp('customer_replied_at')->nullable();
            $table->string('resolution_type', 50)->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('resolved_by_user_id')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();

            $table->index(['status', 'severity']);
            $table->index(['order_id', 'status']);
            $table->index(['root_item_id', 'status']);
            $table->index(['requires_customer_action', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_issues');
    }
};
