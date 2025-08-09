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
        Schema::table('modules', function (Blueprint $table) {
            $table->foreign(['course_id'], 'modules_ibfk_1')->references(['course_id'])->on('courses')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['batch_id'], 'modules_ibfk_2')->references(['batch_id'])->on('course_batches')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->dropForeign('modules_ibfk_1');
            $table->dropForeign('modules_ibfk_2');
        });
    }
};
