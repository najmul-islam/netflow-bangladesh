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
        Schema::create('modules', function (Blueprint $table) {
            $table->char('module_id', 36)->default('uuid()')->primary();
            $table->char('course_id', 36)->index('idx_modules_course');
            $table->char('batch_id', 36)->nullable()->index('idx_modules_batch');
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('sort_order')->nullable()->default(0);
            $table->boolean('is_published')->nullable()->default(false);
            $table->boolean('is_batch_specific')->nullable()->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();

            $table->index(['course_id', 'batch_id', 'sort_order'], 'idx_modules_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
