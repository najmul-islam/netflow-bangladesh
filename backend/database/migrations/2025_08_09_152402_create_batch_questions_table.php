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
        Schema::create('batch_questions', function (Blueprint $table) {
            $table->char('question_id', 36)->default('uuid()')->primary();
            $table->char('assessment_id', 36)->index('idx_batch_questions_assessment');
            $table->text('question_text');
            $table->enum('question_type', ['multiple_choice', 'single_choice', 'true_false', 'fill_blank', 'essay', 'matching', 'coding', 'file_upload'])->index('idx_batch_questions_type');
            $table->decimal('points', 5)->nullable()->default(1);
            $table->text('explanation')->nullable();
            $table->text('media_url')->nullable();
            $table->integer('sort_order')->nullable()->default(0);
            $table->boolean('is_required')->nullable()->default(true);
            $table->enum('difficulty_level', ['easy', 'medium', 'hard'])->nullable()->default('medium')->index('idx_batch_questions_difficulty');
            $table->json('tags')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['assessment_id', 'sort_order'], 'idx_batch_questions_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_questions');
    }
};
