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
            $table->char('notification_id', 36)->primary();
            $table->char('user_id', 36);
            $table->char('batch_id', 36);
            $table->string('title', 255);
            $table->text('content');
            $table->enum('type', ['info', 'success', 'warning', 'error', 'urgent'])->default('info');
            $table->enum('category', [
                'system',
                'batch',
                'class',
                'assessment',
                'forum',
                'message',
                'reminder',
                'certificate',
                'announcement'
            ])->default('batch');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->text('action_url')->nullable();
            $table->json('metadata')->nullable();
            $table->char('schedule_id', 36)->nullable();
            $table->char('assessment_id', 36)->nullable();
            $table->boolean('auto_generated')->default(false);
            $table->timestamp('created_at')->useCurrent();
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