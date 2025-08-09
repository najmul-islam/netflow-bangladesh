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
        Schema::table('course_tags', function (Blueprint $table) {
            $table->foreign(['course_id'], 'course_tags_ibfk_1')->references(['course_id'])->on('courses')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['tag_id'], 'course_tags_ibfk_2')->references(['tag_id'])->on('tags')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_tags', function (Blueprint $table) {
            $table->dropForeign('course_tags_ibfk_1');
            $table->dropForeign('course_tags_ibfk_2');
        });
    }
};
