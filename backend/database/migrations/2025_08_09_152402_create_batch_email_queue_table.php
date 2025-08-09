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
        Schema::create('batch_email_queue', function (Blueprint $table) {
            $table->char('queue_id', 36)->default('uuid()')->primary();
            $table->char('batch_id', 36)->index('idx_batch_email_batch');
            $table->string('recipient_email')->index('idx_batch_email_recipient');
            $table->char('recipient_user_id', 36)->nullable()->index('recipient_user_id');
            $table->string('subject');
            $table->text('content_html');
            $table->text('content_text');
            $table->char('template_id', 36)->nullable()->index('template_id');
            $table->json('template_data')->nullable();
            $table->enum('email_type', ['individual', 'batch_broadcast', 'class_reminder', 'assessment_notification'])->nullable()->default('individual');
            $table->enum('status', ['pending', 'sent', 'failed', 'cancelled'])->nullable()->default('pending')->index('idx_batch_email_status');
            $table->integer('attempts')->nullable()->default(0);
            $table->integer('max_attempts')->nullable()->default(3);
            $table->timestamp('scheduled_at')->nullable()->index('idx_batch_email_scheduled');
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_email_queue');
    }
};
