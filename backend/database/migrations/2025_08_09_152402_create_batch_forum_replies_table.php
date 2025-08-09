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
        Schema::create('batch_forum_replies', function (Blueprint $table) {
            $table->char('reply_id', 36)->default('uuid()')->primary();
            $table->char('topic_id', 36)->index('idx_batch_replies_topic');
            $table->char('batch_id', 36)->index('idx_batch_replies_batch');
            $table->char('parent_reply_id', 36)->nullable()->index('idx_batch_replies_parent');
            $table->text('content');
            $table->boolean('is_solution')->nullable()->default(false);
            $table->integer('like_count')->nullable()->default(0);
            $table->json('attachment_urls')->nullable();
            $table->boolean('is_instructor_reply')->nullable()->default(false);
            $table->char('created_by', 36)->index('created_by');
            $table->timestamp('created_at')->useCurrent()->index('idx_batch_replies_created');
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_forum_replies');
    }
};
