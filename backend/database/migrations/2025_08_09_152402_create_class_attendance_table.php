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
        Schema::create('class_attendance', function (Blueprint $table) {
            $table->char('attendance_id', 36)->default('uuid()')->primary();
            $table->char('schedule_id', 36)->index('idx_attendance_schedule');
            $table->char('user_id', 36)->index('idx_attendance_user');
            $table->enum('status', ['present', 'absent', 'late', 'excused', 'partial'])->nullable()->default('absent')->index('idx_attendance_status');
            $table->timestamp('join_time')->nullable();
            $table->timestamp('leave_time')->nullable();
            $table->integer('duration_minutes')->nullable()->default(0);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('notes')->nullable();
            $table->char('marked_by', 36)->nullable()->index('marked_by');
            $table->timestamp('marked_at')->nullable();
            $table->boolean('auto_marked')->nullable()->default(false);

            $table->index(['user_id', 'status'], 'idx_class_attendance_user_status');
            $table->unique(['schedule_id', 'user_id'], 'unique_schedule_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_attendance');
    }
};
