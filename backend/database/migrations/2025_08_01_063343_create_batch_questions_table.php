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
            $table->char('question_id', 36)->primary();
            $table->char('assessment_id', 36);
            $table->text('question_text');
            $table->enum('question_type', [
                'multiple_choice',
                'single_choice',
                'true_false',
                'fill_blank',
                'essay',
                'matching',
                'coding',
                'file_upload'
            ]);
            $table->decimal('points', 5, 2)->default(1.00);
            $table->text('explanation')->nullable();
            $table->text('media_url')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_required')->default(true);
            $table->enum('difficulty_level', ['easy', 'medium', 'hard'])->default('medium');
            $table->json('tags')->nullable();
            $table->timestamp('created_at')->useCurrent();
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