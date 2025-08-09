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
        Schema::create('batch_activity_logs', function (Blueprint $table) {
            $table->char('log_id', 36)->default('uuid()')->primary();
            $table->char('user_id', 36)->index('idx_batch_activity_user');
            $table->char('batch_id', 36)->index('idx_batch_activity_batch');
            $table->char('lesson_id', 36)->nullable()->index('lesson_id');
            $table->char('schedule_id', 36)->nullable()->index('schedule_id');
            $table->enum('activity_type', ['login', 'logout', 'batch_access', 'lesson_start', 'lesson_complete', 'assessment_start', 'assessment_submit', 'forum_post', 'download', 'certificate_download', 'class_join', 'class_leave', 'message_send'])->index('idx_batch_activity_type');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('session_id')->nullable()->index('idx_batch_activity_session');
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent()->index('idx_batch_activity_created');

            $table->index(['user_id', 'batch_id', 'activity_type'], 'idx_batch_activity_user_batch_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_activity_logs');
    }
};
