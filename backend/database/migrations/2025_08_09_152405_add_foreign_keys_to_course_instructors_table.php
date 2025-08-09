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
        Schema::table('course_instructors', function (Blueprint $table) {
            $table->foreign(['course_id'], 'course_instructors_ibfk_1')->references(['course_id'])->on('courses')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['user_id'], 'course_instructors_ibfk_2')->references(['user_id'])->on('users')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_instructors', function (Blueprint $table) {
            $table->dropForeign('course_instructors_ibfk_1');
            $table->dropForeign('course_instructors_ibfk_2');
        });
    }
};
