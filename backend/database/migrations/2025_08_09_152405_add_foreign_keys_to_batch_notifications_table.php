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
        Schema::table('batch_notifications', function (Blueprint $table) {
            $table->foreign(['user_id'], 'batch_notifications_ibfk_1')->references(['user_id'])->on('users')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['batch_id'], 'batch_notifications_ibfk_2')->references(['batch_id'])->on('course_batches')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['schedule_id'], 'batch_notifications_ibfk_3')->references(['schedule_id'])->on('batch_schedule')->onUpdate('restrict')->onDelete('set null');
            $table->foreign(['assessment_id'], 'batch_notifications_ibfk_4')->references(['assessment_id'])->on('batch_assessments')->onUpdate('restrict')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('batch_notifications', function (Blueprint $table) {
            $table->dropForeign('batch_notifications_ibfk_1');
            $table->dropForeign('batch_notifications_ibfk_2');
            $table->dropForeign('batch_notifications_ibfk_3');
            $table->dropForeign('batch_notifications_ibfk_4');
        });
    }
};
