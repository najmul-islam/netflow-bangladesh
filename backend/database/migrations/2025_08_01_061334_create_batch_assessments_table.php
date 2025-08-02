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
            $table->id();
            $table->char('assessment_id', 36);
            $table->char('batch_id', 36);
            $table->char('lesson_id', 36)->nullable();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->enum('assessment_type', ['quiz', 'assignment', 'exam', 'final_exam', 'survey', 'project', 'presentation'])->default('quiz');
            $table->integer('time_limit_minutes')->nullable();
            $table->integer('max_attempts')->default(1);
            $table->decimal('passing_score', 5, 2)->default(70.00);
            $table->boolean('randomize_questions')->default(false);
            $table->enum('show_results', ['immediately', 'after_due_date', 'manual', 'never'])->default('immediately');
            $table->timestamp('due_date')->nullable();
            $table->timestamp('available_from')->nullable();
            $table->timestamp('available_until')->nullable();
            $table->decimal('weight', 5, 2)->default(0.00);
            $table->boolean('is_published')->default(false);
            $table->boolean('is_final_exam')->default(false);
            $table->boolean('is_proctored')->default(false);
            $table->json('proctoring_settings')->nullable();
            $table->boolean('allow_late_submission')->default(false);
            $table->decimal('late_penalty_percent', 5, 2)->default(10.00);
            $table->boolean('group_assessment')->default(false);
            $table->integer('max_group_size')->default(1);
            $table->char('created_by', 36);
            $table->timestamps();
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