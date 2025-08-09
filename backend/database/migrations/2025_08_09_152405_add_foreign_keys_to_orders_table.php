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
        Schema::table('orders', function (Blueprint $table) {
            $table->foreign(['user_id'], 'orders_ibfk_1')->references(['user_id'])->on('users')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['shipping_address_id'], 'orders_ibfk_2')->references(['address_id'])->on('addresses')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['billing_address_id'], 'orders_ibfk_3')->references(['address_id'])->on('addresses')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['payment_id'], 'orders_ibfk_4')->references(['payment_id'])->on('payments')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign('orders_ibfk_1');
            $table->dropForeign('orders_ibfk_2');
            $table->dropForeign('orders_ibfk_3');
            $table->dropForeign('orders_ibfk_4');
        });
    }
};
