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
            $table->char('progress_id', 36)->default('uuid()')->primary();
            $table->char('user_id', 36)->index('idx_batch_progress_user');
            $table->char('batch_id', 36)->index('idx_batch_progress_batch');
            $table->char('lesson_id', 36)->index('idx_batch_progress_lesson');
            $table->enum('status', ['not_started', 'in_progress', 'completed', 'skipped'])->nullable()->default('not_started')->index('idx_batch_progress_status');
            $table->decimal('progress_percentage', 5)->nullable()->default(0);
            $table->integer('time_spent_minutes')->nullable()->default(0);
            $table->timestamp('first_accessed')->nullable();
            $table->timestamp('last_accessed')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->text('instructor_feedback')->nullable();
            $table->decimal('grade', 5)->nullable();

            $table->index(['user_id', 'batch_id', 'status'], 'idx_batch_lesson_progress_user_batch');
            $table->unique(['user_id', 'batch_id', 'lesson_id'], 'unique_user_batch_lesson');
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
