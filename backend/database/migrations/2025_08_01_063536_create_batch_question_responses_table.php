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
        Schema::create('batch_question_responses', function (Blueprint $table) {
            $table->char('response_id', 36)->primary();
            $table->char('attempt_id', 36);
            $table->char('question_id', 36);
            $table->json('selected_options')->nullable();
            $table->text('text_response')->nullable();
            $table->json('file_uploads')->nullable();
            $table->decimal('points_earned', 5, 2)->default(0.00);
            $table->boolean('is_correct')->nullable();
            $table->text('feedback')->nullable();
            $table->integer('time_spent_seconds')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_question_responses');
    }
};