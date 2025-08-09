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
        Schema::create('batch_notification_templates', function (Blueprint $table) {
            $table->char('template_id', 36)->default('uuid()')->primary();
            $table->string('name', 100)->unique('name');
            $table->string('subject');
            $table->text('content_html');
            $table->text('content_text');
            $table->enum('template_type', ['batch_enrollment', 'class_reminder', 'assignment_due', 'grade_released', 'certificate_issued', 'batch_announcement', 'attendance_warning']);
            $table->json('variables')->nullable();
            $table->boolean('is_active')->nullable()->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_notification_templates');
    }
};
