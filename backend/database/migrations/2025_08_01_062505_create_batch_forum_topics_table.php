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
            $table->char('topic_id', 36)->primary();
            $table->char('forum_id', 36);
            $table->char('batch_id', 36);
            $table->string('title', 255);
            $table->text('content');
            $table->enum('topic_type', ['discussion', 'question', 'announcement', 'poll'])->default('discussion');
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->boolean('is_solved')->default(false);
            $table->integer('view_count')->default(0);
            $table->integer('reply_count')->default(0);
            $table->integer('like_count')->default(0);
            $table->timestamp('last_reply_at')->nullable();
            $table->char('last_reply_by', 36)->nullable();
            $table->json('tags')->nullable();
            $table->char('created_by', 36);
            $table->timestamps();
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