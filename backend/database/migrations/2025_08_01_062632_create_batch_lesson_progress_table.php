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
        Schema::create('batch_lesson_progress', function (Blueprint $table) {
            $table->char('progress_id', 36)->primary();
            $table->char('user_id', 36);
            $table->char('batch_id', 36);
            $table->char('lesson_id', 36);
            $table->enum('status', ['not_started', 'in_progress', 'completed', 'skipped'])->default('not_started');
            $table->decimal('progress_percentage', 5, 2)->default(0.00);
            $table->integer('time_spent_minutes')->default(0);
            $table->timestamp('first_accessed')->nullable();
            $table->timestamp('last_accessed')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->text('instructor_feedback')->nullable();
            $table->decimal('grade', 5, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_lesson_progress');
    }
};