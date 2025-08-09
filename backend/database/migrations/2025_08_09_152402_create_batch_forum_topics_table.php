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
        Schema::create('batch_forum_topics', function (Blueprint $table) {
            $table->char('topic_id', 36)->default('uuid()')->primary();
            $table->char('forum_id', 36)->index('idx_batch_topics_forum');
            $table->char('batch_id', 36)->index('idx_batch_topics_batch');
            $table->string('title');
            $table->text('content');
            $table->enum('topic_type', ['discussion', 'question', 'announcement', 'poll'])->nullable()->default('discussion')->index('idx_batch_topics_type');
            $table->boolean('is_pinned')->nullable()->default(false);
            $table->boolean('is_locked')->nullable()->default(false);
            $table->boolean('is_solved')->nullable()->default(false);
            $table->integer('view_count')->nullable()->default(0);
            $table->integer('reply_count')->nullable()->default(0);
            $table->integer('like_count')->nullable()->default(0);
            $table->timestamp('last_reply_at')->nullable()->index('idx_batch_topics_last_reply');
            $table->char('last_reply_by', 36)->nullable()->index('last_reply_by');
            $table->json('tags')->nullable();
            $table->char('created_by', 36)->index('created_by');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();

            $table->fullText(['title', 'content'], 'title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_forum_topics');
    }
};
