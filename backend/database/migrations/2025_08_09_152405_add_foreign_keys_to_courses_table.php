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
        Schema::table('courses', function (Blueprint $table) {
            $table->foreign(['category_id'], 'courses_ibfk_1')->references(['category_id'])->on('categories')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['created_by'], 'courses_ibfk_2')->references(['user_id'])->on('users')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropForeign('courses_ibfk_1');
            $table->dropForeign('courses_ibfk_2');
        });
    }
};
