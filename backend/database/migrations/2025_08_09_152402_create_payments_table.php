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
        Schema::create('payments', function (Blueprint $table) {
            $table->char('payment_id', 36)->primary();
            $table->char('user_id', 36)->nullable()->index('user_id');
            $table->decimal('amount', 10);
            $table->string('currency', 10)->nullable()->default('USD');
            $table->string('method', 50)->nullable();
            $table->string('transaction_id')->nullable();
            $table->enum('status', ['initiated', 'success', 'failed', 'refunded'])->nullable()->default('initiated');
            $table->json('metadata')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
