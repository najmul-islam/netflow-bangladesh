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
        Schema::create('batch_schedule_attendances', function (Blueprint $table) {
            $table->char('attendance_id', 36)->primary();
            $table->char('schedule_id', 36);
            $table->char('user_id', 36);
            $table->timestamp('join_time')->nullable();
            $table->timestamp('leave_time')->nullable();
            $table->integer('duration_minutes')->default(0);
            $table->enum('attendance_status', ['present', 'absent', 'late', 'excused'])->default('present');
            $table->json('notes')->nullable();
            $table->char('marked_by', 36)->nullable();
            $table->timestamp('marked_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_schedule_attendances');
    }
};