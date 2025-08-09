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
        Schema::create('batch_messages', function (Blueprint $table) {
            $table->char('message_id', 36)->default('uuid()')->primary();
            $table->char('batch_id', 36)->index('idx_batch_messages_batch');
            $table->char('sender_id', 36)->index('idx_batch_messages_sender');
            $table->char('recipient_id', 36)->index('idx_batch_messages_recipient');
            $table->string('subject')->nullable();
            $table->text('content');
            $table->enum('message_type', ['direct', 'announcement', 'assignment_feedback', 'grade_notification'])->nullable()->default('direct');
            $table->boolean('is_read')->nullable()->default(false)->index('idx_batch_messages_read');
            $table->timestamp('read_at')->nullable();
            $table->char('parent_message_id', 36)->nullable()->index('parent_message_id');
            $table->json('attachment_urls')->nullable();
            $table->boolean('is_system_message')->nullable()->default(false);
            $table->timestamp('created_at')->useCurrent()->index('idx_batch_messages_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_messages');
    }
};
