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
        Schema::create('course_instructors', function (Blueprint $table) {
            $table->char('course_id', 36);
            $table->char('user_id', 36)->index('user_id');
            $table->enum('role', ['primary', 'secondary', 'assistant'])->nullable()->default('primary');
            $table->timestamp('assigned_at')->useCurrent();

            $table->primary(['course_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_instructors');
    }
};
