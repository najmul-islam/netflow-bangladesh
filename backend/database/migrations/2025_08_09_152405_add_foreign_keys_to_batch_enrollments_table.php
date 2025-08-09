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
        Schema::table('batch_enrollments', function (Blueprint $table) {
            $table->foreign(['user_id'], 'batch_enrollments_ibfk_1')->references(['user_id'])->on('users')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['batch_id'], 'batch_enrollments_ibfk_2')->references(['batch_id'])->on('course_batches')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['enrolled_by'], 'batch_enrollments_ibfk_3')->references(['user_id'])->on('users')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['transfer_from_batch_id'], 'batch_enrollments_ibfk_4')->references(['batch_id'])->on('course_batches')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('batch_enrollments', function (Blueprint $table) {
            $table->dropForeign('batch_enrollments_ibfk_1');
            $table->dropForeign('batch_enrollments_ibfk_2');
            $table->dropForeign('batch_enrollments_ibfk_3');
            $table->dropForeign('batch_enrollments_ibfk_4');
        });
    }
};
