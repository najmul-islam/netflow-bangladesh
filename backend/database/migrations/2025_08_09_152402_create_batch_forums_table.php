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
        Schema::create('batch_forums', function (Blueprint $table) {
            $table->char('forum_id', 36)->default('uuid()')->primary();
            $table->char('batch_id', 36)->index('idx_batch_forums_batch');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('forum_type', ['general', 'announcements', 'q_and_a', 'assignments', 'projects', 'social'])->nullable()->default('general')->index('idx_batch_forums_type');
            $table->integer('sort_order')->nullable()->default(0);
            $table->boolean('is_locked')->nullable()->default(false);
            $table->boolean('is_announcement_only')->nullable()->default(false);
            $table->boolean('auto_subscribe_students')->nullable()->default(true);
            $table->char('created_by', 36)->index('created_by');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['batch_id', 'forum_type'], 'idx_batch_forums_batch_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_forums');
    }
};
