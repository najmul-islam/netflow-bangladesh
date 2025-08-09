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
        Schema::table('lessons', function (Blueprint $table) {
            $table->foreign(['module_id'], 'lessons_ibfk_1')->references(['module_id'])->on('modules')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['batch_id'], 'lessons_ibfk_2')->references(['batch_id'])->on('course_batches')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropForeign('lessons_ibfk_1');
            $table->dropForeign('lessons_ibfk_2');
        });
    }
};
