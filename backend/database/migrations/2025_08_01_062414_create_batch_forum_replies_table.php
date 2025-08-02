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
            $table->char('reply_id', 36)->primary();
            $table->char('topic_id', 36);
            $table->char('batch_id', 36);
            $table->char('parent_reply_id', 36)->nullable();
            $table->text('content');
            $table->boolean('is_solution')->default(false);
            $table->integer('like_count')->default(0);
            $table->json('attachment_urls')->nullable();
            $table->boolean('is_instructor_reply')->default(false);
            $table->char('created_by', 36);
            $table->timestamps();
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