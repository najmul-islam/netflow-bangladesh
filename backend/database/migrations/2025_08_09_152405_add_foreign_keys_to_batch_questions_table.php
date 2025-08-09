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
        Schema::table('batch_questions', function (Blueprint $table) {
            $table->foreign(['assessment_id'], 'batch_questions_ibfk_1')->references(['assessment_id'])->on('batch_assessments')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('batch_questions', function (Blueprint $table) {
            $table->dropForeign('batch_questions_ibfk_1');
        });
    }
};
