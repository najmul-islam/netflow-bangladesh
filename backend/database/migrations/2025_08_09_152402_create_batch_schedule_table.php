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
        Schema::create('batch_schedule', function (Blueprint $table) {
            $table->char('schedule_id', 36)->default('uuid()')->primary();
            $table->char('batch_id', 36)->index('idx_schedule_batch');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('session_type', ['live_class', 'lab_session', 'exam', 'workshop', 'review', 'project_presentation', 'guest_lecture'])->nullable()->default('live_class')->index('idx_schedule_type');
            $table->timestamp('start_datetime')->useCurrentOnUpdate()->useCurrent();
            $table->dateTime('end_datetime');
            $table->integer('duration_minutes')->nullable()->storedAs('timestampdiff(MINUTE,`start_datetime`,`end_datetime`)');
            $table->string('timezone', 50)->nullable()->default('UTC');
            $table->boolean('is_mandatory')->nullable()->default(true);
            $table->integer('max_attendees')->nullable();
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled', 'postponed'])->nullable()->default('scheduled')->index('idx_schedule_status');
            $table->enum('meeting_platform', ['zoom', 'google_meet', 'microsoft_teams', 'webex', 'custom', 'offline'])->nullable()->default('zoom')->index('idx_schedule_platform');
            $table->text('meeting_url')->nullable();
            $table->string('meeting_id')->nullable();
            $table->string('meeting_password')->nullable();
            $table->string('dial_in_number', 100)->nullable();
            $table->string('meeting_room', 100)->nullable();
            $table->text('backup_meeting_url')->nullable();
            $table->text('agenda')->nullable();
            $table->text('prerequisites')->nullable();
            $table->text('materials_needed')->nullable();
            $table->text('recording_url')->nullable();
            $table->string('recording_password')->nullable();
            $table->boolean('auto_record')->nullable()->default(true);
            $table->boolean('send_reminder')->nullable()->default(true);
            $table->json('reminder_minutes')->nullable()->default('json_array(1440,60,15)');
            $table->char('created_by', 36)->index('created_by');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();

            $table->index(['batch_id', 'start_datetime'], 'idx_batch_schedule_datetime_batch');
            $table->index(['start_datetime', 'end_datetime'], 'idx_schedule_datetime');
            $table->fullText(['title', 'description'], 'title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_schedule');
    }
};
