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
            $table->char('enrollment_id', 36)->default('uuid()')->primary();
            $table->char('user_id', 36)->index('idx_batch_enrollments_user');
            $table->char('batch_id', 36)->index('idx_batch_enrollments_batch');
            $table->timestamp('enrollment_date')->useCurrent()->index('idx_batch_enrollments_date');
            $table->timestamp('completion_date')->nullable();
            $table->decimal('progress_percentage', 5)->nullable()->default(0);
            $table->timestamp('last_accessed')->nullable();
            $table->enum('status', ['active', 'completed', 'dropped', 'suspended', 'transferred'])->nullable()->default('active')->index('idx_batch_enrollments_status');
            $table->decimal('attendance_percentage', 5)->nullable()->default(0);
            $table->boolean('final_exam_passed')->nullable()->default(false)->index('idx_batch_enrollments_exam_passed');
            $table->decimal('final_exam_score', 5)->nullable();
            $table->boolean('certificate_issued')->nullable()->default(false);
            $table->timestamp('certificate_issued_at')->nullable();
            $table->char('enrolled_by', 36)->nullable()->index('enrolled_by');
            $table->char('transfer_from_batch_id', 36)->nullable()->index('transfer_from_batch_id');
            $table->text('transfer_reason')->nullable();
            $table->timestamp('transfer_date')->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'partial', 'refunded', 'waived'])->nullable()->default('pending')->index('idx_batch_enrollments_payment');

            $table->index(['batch_id', 'status'], 'idx_batch_enrollments_batch_status');
            $table->index(['user_id', 'status'], 'idx_batch_enrollments_user_status');
            $table->unique(['user_id', 'batch_id'], 'unique_user_batch');
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
