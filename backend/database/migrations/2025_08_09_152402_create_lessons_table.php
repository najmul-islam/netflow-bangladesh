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
        Schema::create('lessons', function (Blueprint $table) {
            $table->char('lesson_id', 36)->default('uuid()')->primary();
            $table->char('module_id', 36)->index('idx_lessons_module');
            $table->char('batch_id', 36)->nullable()->index('idx_lessons_batch');
            $table->string('title');
            $table->enum('content_type', ['video', 'text', 'audio', 'interactive', 'document', 'quiz', 'assignment', 'live_session'])->index('idx_lessons_type');
            $table->text('content_url')->nullable();
            $table->longText('content_text')->nullable();
            $table->integer('duration_minutes')->nullable()->default(0);
            $table->integer('sort_order')->nullable()->default(0);
            $table->boolean('is_free_preview')->nullable()->default(false);
            $table->boolean('is_published')->nullable()->default(false);
            $table->boolean('is_batch_specific')->nullable()->default(false);
            $table->date('scheduled_date')->nullable();
            $table->time('scheduled_time')->nullable();
            $table->timestamp('availability_start')->nullable();
            $table->timestamp('availability_end')->nullable();
            $table->json('settings')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();

            $table->index(['availability_start', 'availability_end'], 'idx_lessons_availability');
            $table->index(['module_id', 'sort_order'], 'idx_lessons_order');
            $table->index(['scheduled_date', 'scheduled_time'], 'idx_lessons_scheduled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
