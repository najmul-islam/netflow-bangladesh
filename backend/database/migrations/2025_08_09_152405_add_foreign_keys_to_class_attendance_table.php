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
        Schema::table('class_attendance', function (Blueprint $table) {
            $table->foreign(['schedule_id'], 'class_attendance_ibfk_1')->references(['schedule_id'])->on('batch_schedule')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['user_id'], 'class_attendance_ibfk_2')->references(['user_id'])->on('users')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['marked_by'], 'class_attendance_ibfk_3')->references(['user_id'])->on('users')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('class_attendance', function (Blueprint $table) {
            $table->dropForeign('class_attendance_ibfk_1');
            $table->dropForeign('class_attendance_ibfk_2');
            $table->dropForeign('class_attendance_ibfk_3');
        });
    }
};
