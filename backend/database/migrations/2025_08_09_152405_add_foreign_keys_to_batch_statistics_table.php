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
        Schema::table('batch_statistics', function (Blueprint $table) {
            $table->foreign(['batch_id'], 'batch_statistics_ibfk_1')->references(['batch_id'])->on('course_batches')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('batch_statistics', function (Blueprint $table) {
            $table->dropForeign('batch_statistics_ibfk_1');
        });
    }
};
