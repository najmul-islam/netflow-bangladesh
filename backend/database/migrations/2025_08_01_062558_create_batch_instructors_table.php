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
        Schema::create('batch_instructors', function (Blueprint $table) {
            $table->char('batch_id', 36);
            $table->char('user_id', 36);
            $table->enum('role', ['primary', 'secondary', 'assistant', 'guest'])->default('primary');
            $table->timestamp('assigned_at')->useCurrent();
            $table->char('assigned_by', 36)->nullable();
            $table->boolean('is_active')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_instructors');
    }
};