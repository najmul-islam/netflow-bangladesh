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
        Schema::table('lesson_resources', function (Blueprint $table) {
            $table->foreign(['lesson_id'], 'lesson_resources_ibfk_1')->references(['lesson_id'])->on('lessons')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['batch_id'], 'lesson_resources_ibfk_2')->references(['batch_id'])->on('course_batches')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lesson_resources', function (Blueprint $table) {
            $table->dropForeign('lesson_resources_ibfk_1');
            $table->dropForeign('lesson_resources_ibfk_2');
        });
    }
};
