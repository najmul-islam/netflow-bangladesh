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
        Schema::create('batch_question_options', function (Blueprint $table) {
            $table->char('option_id', 36)->default('uuid()')->primary();
            $table->char('question_id', 36)->index('idx_batch_options_question');
            $table->text('option_text');
            $table->boolean('is_correct')->nullable()->default(false);
            $table->integer('sort_order')->nullable()->default(0);
            $table->text('explanation')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_question_options');
    }
};
