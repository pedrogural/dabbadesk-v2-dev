<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_attention_flags', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('order_item_id')->nullable();
            $table->unsignedBigInteger('root_item_id');
            $table->unsignedBigInteger('order_item_purchase_id')->nullable();
            $table->string('attention_type', 64)->default('other');
            $table->text('note')->nullable();
            $table->timestamp('cleared_at')->nullable();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->unsignedBigInteger('cleared_by_user_id')->nullable();
            $table->unsignedBigInteger('updated_by_user_id')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'cleared_at']);
            $table->index(['root_item_id', 'cleared_at']);
            $table->index(['order_item_purchase_id', 'cleared_at'], 'paf_purchase_clear_idx');
            $table->index('attention_type');

            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('order_item_id')->references('id')->on('order_items')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('root_item_id')->references('id')->on('order_items')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('order_item_purchase_id', 'paf_purchase_fk')->references('id')->on('order_item_purchases')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('cleared_by_user_id')->references('id')->on('users')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('updated_by_user_id')->references('id')->on('users')->nullOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_attention_flags');
    }
};
