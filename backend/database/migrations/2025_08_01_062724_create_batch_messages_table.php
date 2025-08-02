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
            $table->char('message_id', 36)->primary();
            $table->char('batch_id', 36);
            $table->char('sender_id', 36);
            $table->char('recipient_id', 36);
            $table->string('subject', 255)->nullable();
            $table->text('content');
            $table->enum('message_type', ['direct', 'announcement', 'assignment_feedback', 'grade_notification'])->default('direct');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->char('parent_message_id', 36)->nullable();
            $table->json('attachment_urls')->nullable();
            $table->boolean('is_system_message')->default(false);
            $table->timestamp('created_at')->useCurrent();
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