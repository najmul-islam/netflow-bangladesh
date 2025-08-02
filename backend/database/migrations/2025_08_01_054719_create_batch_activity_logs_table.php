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
            $table->id();
            $table->char('user_id', 36);
            $table->char('batch_id', 36);
            $table->char('lesson_id', 36)->nullable();
            $table->char('schedule_id', 36)->nullable();
            // foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('batch_id')->references('id')->on('course_batches')->onDelete('cascade');
            $table->foreign('lesson_id')->references('id')->on('course_lessons')->onDelete('set null');
            $table->foreign('schedule_id')->references('id')->on('batch_schedules')->onDelete('set null');

            $table->enum('activity_type', ['login', 'logout', 'batch_access', 'lesson_start', 'lesson_complete', 'assessment_start', 'assessment_submit', 'forum_post', 'download', 'certificate_download', 'class_join', 'class_leave', 'message_send']);
            $table->string('ip_address', 45)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
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