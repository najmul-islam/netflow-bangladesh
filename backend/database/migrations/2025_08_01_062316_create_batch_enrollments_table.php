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
        Schema::create('batch_enrollments', function (Blueprint $table) {
            $table->char('enrollment_id', 36)->primary();
            $table->char('user_id', 36);
            $table->char('batch_id', 36);
            $table->timestamp('enrollment_date')->useCurrent();
            $table->timestamp('completion_date')->nullable();
            $table->decimal('progress_percentage', 5, 2)->default(0.00);
            $table->timestamp('last_accessed')->nullable();
            $table->enum('status', ['active', 'completed', 'dropped', 'suspended', 'transferred'])->default('active');
            $table->decimal('attendance_percentage', 5, 2)->default(0.00);
            $table->boolean('final_exam_passed')->default(false);
            $table->decimal('final_exam_score', 5, 2)->nullable();
            $table->boolean('certificate_issued')->default(false);
            $table->timestamp('certificate_issued_at')->nullable();
            $table->char('enrolled_by', 36)->nullable();
            $table->char('transfer_from_batch_id', 36)->nullable();
            $table->text('transfer_reason')->nullable();
            $table->timestamp('transfer_date')->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'partial', 'refunded', 'waived'])->default('pending');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_enrollments');
    }
};