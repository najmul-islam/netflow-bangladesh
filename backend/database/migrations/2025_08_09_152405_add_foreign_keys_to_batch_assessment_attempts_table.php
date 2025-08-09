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
        Schema::table('batch_assessment_attempts', function (Blueprint $table) {
            $table->foreign(['assessment_id'], 'batch_assessment_attempts_ibfk_1')->references(['assessment_id'])->on('batch_assessments')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['user_id'], 'batch_assessment_attempts_ibfk_2')->references(['user_id'])->on('users')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['batch_id'], 'batch_assessment_attempts_ibfk_3')->references(['batch_id'])->on('course_batches')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['graded_by'], 'batch_assessment_attempts_ibfk_4')->references(['user_id'])->on('users')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('batch_assessment_attempts', function (Blueprint $table) {
            $table->dropForeign('batch_assessment_attempts_ibfk_1');
            $table->dropForeign('batch_assessment_attempts_ibfk_2');
            $table->dropForeign('batch_assessment_attempts_ibfk_3');
            $table->dropForeign('batch_assessment_attempts_ibfk_4');
        });
    }
};
