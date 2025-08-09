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
        Schema::table('batch_student_performance', function (Blueprint $table) {
            $table->foreign(['user_id'], 'batch_student_performance_ibfk_1')->references(['user_id'])->on('users')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['batch_id'], 'batch_student_performance_ibfk_2')->references(['batch_id'])->on('course_batches')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('batch_student_performance', function (Blueprint $table) {
            $table->dropForeign('batch_student_performance_ibfk_1');
            $table->dropForeign('batch_student_performance_ibfk_2');
        });
    }
};
