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
        Schema::table('batch_activity_logs', function (Blueprint $table) {
            $table->foreign(['user_id'], 'batch_activity_logs_ibfk_1')->references(['user_id'])->on('users')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['batch_id'], 'batch_activity_logs_ibfk_2')->references(['batch_id'])->on('course_batches')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['lesson_id'], 'batch_activity_logs_ibfk_3')->references(['lesson_id'])->on('lessons')->onUpdate('restrict')->onDelete('set null');
            $table->foreign(['schedule_id'], 'batch_activity_logs_ibfk_4')->references(['schedule_id'])->on('batch_schedule')->onUpdate('restrict')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('batch_activity_logs', function (Blueprint $table) {
            $table->dropForeign('batch_activity_logs_ibfk_1');
            $table->dropForeign('batch_activity_logs_ibfk_2');
            $table->dropForeign('batch_activity_logs_ibfk_3');
            $table->dropForeign('batch_activity_logs_ibfk_4');
        });
    }
};
