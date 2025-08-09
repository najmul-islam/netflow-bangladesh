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
        Schema::create('batch_assessments', function (Blueprint $table) {
            $table->char('assessment_id', 36)->default('uuid()')->primary();
            $table->char('batch_id', 36)->index('idx_batch_assessments_batch');
            $table->char('lesson_id', 36)->nullable()->index('idx_batch_assessments_lesson');
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->enum('assessment_type', ['quiz', 'assignment', 'exam', 'final_exam', 'survey', 'project', 'presentation'])->nullable()->default('quiz')->index('idx_batch_assessments_type');
            $table->integer('time_limit_minutes')->nullable();
            $table->integer('max_attempts')->nullable()->default(1);
            $table->decimal('passing_score', 5)->nullable()->default(70);
            $table->boolean('randomize_questions')->nullable()->default(false);
            $table->enum('show_results', ['immediately', 'after_due_date', 'manual', 'never'])->nullable()->default('immediately');
            $table->timestamp('due_date')->nullable()->index('idx_batch_assessments_due_date');
            $table->timestamp('available_from')->nullable();
            $table->timestamp('available_until')->nullable();
            $table->decimal('weight', 5)->nullable()->default(0);
            $table->boolean('is_published')->nullable()->default(false);
            $table->boolean('is_final_exam')->nullable()->default(false)->index('idx_batch_assessments_final');
            $table->boolean('is_proctored')->nullable()->default(false);
            $table->json('proctoring_settings')->nullable();
            $table->boolean('allow_late_submission')->nullable()->default(false);
            $table->decimal('late_penalty_percent', 5)->nullable()->default(10);
            $table->boolean('group_assessment')->nullable()->default(false);
            $table->integer('max_group_size')->nullable()->default(1);
            $table->char('created_by', 36)->index('created_by');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_assessments');
    }
};
