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
        Schema::create('batch_notifications', function (Blueprint $table) {
            $table->char('notification_id', 36)->default('uuid()')->primary();
            $table->char('user_id', 36)->index('idx_batch_notifications_user');
            $table->char('batch_id', 36)->index('idx_batch_notifications_batch');
            $table->string('title');
            $table->text('content');
            $table->enum('type', ['info', 'success', 'warning', 'error', 'urgent'])->nullable()->default('info');
            $table->enum('category', ['system', 'batch', 'class', 'assessment', 'forum', 'message', 'reminder', 'certificate', 'announcement'])->nullable()->default('batch');
            $table->boolean('is_read')->nullable()->default(false);
            $table->timestamp('read_at')->nullable();
            $table->text('action_url')->nullable();
            $table->json('metadata')->nullable();
            $table->char('schedule_id', 36)->nullable()->index('schedule_id');
            $table->char('assessment_id', 36)->nullable()->index('assessment_id');
            $table->boolean('auto_generated')->nullable()->default(false);
            $table->timestamp('created_at')->useCurrent()->index('idx_batch_notifications_created');

            $table->index(['user_id', 'batch_id', 'is_read'], 'idx_batch_notifications_unread');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_notifications');
    }
};
