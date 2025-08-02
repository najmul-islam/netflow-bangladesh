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
        Schema::create('batch_schedules', function (Blueprint $table) {
            $table->char('schedule_id', 36)->primary();
            $table->char('batch_id', 36);
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->enum('session_type', [
                'live_class',
                'lab_session',
                'exam',
                'workshop',
                'review',
                'project_presentation',
                'guest_lecture'
            ])->default('live_class');
            $table->timestamp('start_datetime')->useCurrent();
            $table->dateTime('end_datetime');
            $table->string('timezone', 50)->default('UTC');
            $table->boolean('is_mandatory')->default(true);
            $table->integer('max_attendees')->nullable();
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled', 'postponed'])->default('scheduled');
            $table->enum('meeting_platform', [
                'zoom',
                'google_meet',
                'microsoft_teams',
                'webex',
                'custom',
                'offline'
            ])->default('zoom');
            $table->text('meeting_url')->nullable();
            $table->string('meeting_id', 255)->nullable();
            $table->string('meeting_password', 255)->nullable();
            $table->string('dial_in_number', 100)->nullable();
            $table->string('meeting_room', 100)->nullable();
            $table->text('backup_meeting_url')->nullable();
            $table->text('agenda')->nullable();
            $table->text('prerequisites')->nullable();
            $table->text('materials_needed')->nullable();
            $table->text('recording_url')->nullable();
            $table->string('recording_password', 255)->nullable();
            $table->boolean('auto_record')->default(true);
            $table->boolean('send_reminder')->default(true);
            $table->json('reminder_minutes')->default(json_encode([1440, 60, 15]));
            $table->char('created_by', 36);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_schedules');
    }
};