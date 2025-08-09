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
        Schema::create('order_items', function (Blueprint $table) {
            $table->char('order_item_id', 36)->primary();
            $table->char('order_id', 36)->index('order_id');
            $table->char('product_id', 36)->index('product_id');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10);
            $table->decimal('total_price', 10)->nullable()->storedAs('`quantity` * `unit_price`');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
