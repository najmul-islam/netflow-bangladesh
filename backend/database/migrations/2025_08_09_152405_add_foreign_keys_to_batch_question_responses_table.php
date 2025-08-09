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
        Schema::table('batch_question_responses', function (Blueprint $table) {
            $table->foreign(['attempt_id'], 'batch_question_responses_ibfk_1')->references(['attempt_id'])->on('batch_assessment_attempts')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['question_id'], 'batch_question_responses_ibfk_2')->references(['question_id'])->on('batch_questions')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('batch_question_responses', function (Blueprint $table) {
            $table->dropForeign('batch_question_responses_ibfk_1');
            $table->dropForeign('batch_question_responses_ibfk_2');
        });
    }
};
