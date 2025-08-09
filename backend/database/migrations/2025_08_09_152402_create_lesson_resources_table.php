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
        Schema::create('lesson_resources', function (Blueprint $table) {
            $table->char('resource_id', 36)->default('uuid()')->primary();
            $table->char('lesson_id', 36)->index('idx_resources_lesson');
            $table->char('batch_id', 36)->nullable()->index('idx_resources_batch');
            $table->string('title');
            $table->text('file_url');
            $table->string('file_type', 50)->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->boolean('is_free')->nullable()->default(false)->index('idx_resources_free');
            $table->boolean('is_batch_specific')->nullable()->default(false);
            $table->integer('download_count')->nullable()->default(0);
            $table->json('access_restrictions')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_resources');
    }
};
