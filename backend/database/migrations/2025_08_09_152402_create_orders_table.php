<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->char('order_id', 36)->primary();
            $table->char('user_id', 36)->index('user_id');
            $table->char('shipping_address_id', 36)->nullable()->index('shipping_address_id');
            $table->char('billing_address_id', 36)->nullable()->index('billing_address_id');
            $table->decimal('total_amount', 10);
            $table->enum('status', ['pending', 'processing', 'shipped', 'delivered', 'cancelled'])->nullable()->default('pending');
            $table->enum('payment_status', ['unpaid', 'paid', 'refunded', 'failed'])->nullable()->default('unpaid');
            $table->char('payment_id', 36)->nullable()->index('payment_id');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
