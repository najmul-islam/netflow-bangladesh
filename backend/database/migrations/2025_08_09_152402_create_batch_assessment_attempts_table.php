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
        Schema::create('batch_assessment_attempts', function (Blueprint $table) {
            $table->char('attempt_id', 36)->default('uuid()')->primary();
            $table->char('assessment_id', 36)->index('idx_batch_attempts_assessment');
            $table->char('user_id', 36)->index('idx_batch_attempts_user');
            $table->char('batch_id', 36)->index('idx_batch_attempts_batch');
            $table->integer('attempt_number')->nullable()->default(1);
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('submitted_at')->nullable();
            $table->integer('time_spent_minutes')->nullable()->default(0);
            $table->decimal('score', 5)->nullable();
            $table->decimal('max_score', 5);
            $table->decimal('percentage', 5)->nullable();
            $table->boolean('passed')->nullable()->default(false)->index('idx_batch_attempts_passed');
            $table->enum('status', ['in_progress', 'submitted', 'graded', 'expired', 'late_submission'])->nullable()->default('in_progress')->index('idx_batch_attempts_status');
            $table->boolean('is_late')->nullable()->default(false);
            $table->decimal('late_penalty_applied', 5)->nullable()->default(0);
            $table->char('graded_by', 36)->nullable()->index('graded_by');
            $table->timestamp('graded_at')->nullable();
            $table->text('feedback')->nullable();
            $table->decimal('plagiarism_score', 5)->nullable();
            $table->json('proctoring_violations')->nullable();

            $table->index(['user_id', 'batch_id', 'passed'], 'idx_batch_assessment_attempts_user_batch');
            $table->unique(['user_id', 'batch_id', 'assessment_id', 'attempt_number'], 'unique_user_batch_assessment_attempt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_assessment_attempts');
    }
};
