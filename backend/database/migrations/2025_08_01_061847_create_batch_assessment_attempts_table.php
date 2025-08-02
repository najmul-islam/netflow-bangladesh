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
            $table->char('attempt_id', 36)->primary();
            $table->char('assessment_id', 36);
            $table->char('user_id', 36);
            $table->char('batch_id', 36);
            $table->integer('attempt_number')->default(1);
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('submitted_at')->nullable();
            $table->integer('time_spent_minutes')->default(0);
            $table->decimal('score', 5, 2)->nullable();
            $table->decimal('max_score', 5, 2);
            $table->decimal('percentage', 5, 2)->nullable();
            $table->boolean('passed')->default(false);
            $table->enum('status', ['in_progress', 'submitted', 'graded', 'expired', 'late_submission'])->default('in_progress');
            $table->boolean('is_late')->default(false);
            $table->decimal('late_penalty_applied', 5, 2)->default(0.00);
            $table->char('graded_by', 36)->nullable();
            $table->timestamp('graded_at')->nullable();
            $table->text('feedback')->nullable();
            $table->decimal('plagiarism_score', 5, 2)->nullable();
            $table->json('proctoring_violations')->nullable();
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