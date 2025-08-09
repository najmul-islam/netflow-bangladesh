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
        Schema::table('batch_lesson_progress', function (Blueprint $table) {
            $table->foreign(['user_id'], 'batch_lesson_progress_ibfk_1')->references(['user_id'])->on('users')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['batch_id'], 'batch_lesson_progress_ibfk_2')->references(['batch_id'])->on('course_batches')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['lesson_id'], 'batch_lesson_progress_ibfk_3')->references(['lesson_id'])->on('lessons')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('batch_lesson_progress', function (Blueprint $table) {
            $table->dropForeign('batch_lesson_progress_ibfk_1');
            $table->dropForeign('batch_lesson_progress_ibfk_2');
            $table->dropForeign('batch_lesson_progress_ibfk_3');
        });
    }
};
