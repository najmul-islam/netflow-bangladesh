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
        Schema::create('batch_certificates', function (Blueprint $table) {
            $table->char('certificate_id', 36)->default('uuid()')->primary();
            $table->char('user_id', 36)->index('idx_batch_certificates_user');
            $table->char('batch_id', 36)->index('idx_batch_certificates_batch');
            $table->char('template_id', 36)->index('template_id');
            $table->string('certificate_number', 100)->unique('certificate_number');
            $table->timestamp('issued_date')->useCurrent()->index('idx_batch_certificates_issued');
            $table->timestamp('expiry_date')->nullable();
            $table->string('verification_code', 100)->index('idx_batch_certificates_verification');
            $table->text('certificate_url')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_revoked')->nullable()->default(false);
            $table->timestamp('revoked_at')->nullable();
            $table->char('revoked_by', 36)->nullable()->index('revoked_by');
            $table->text('revocation_reason')->nullable();
            $table->date('batch_completion_date')->nullable();
            $table->decimal('final_grade', 5)->nullable();
            $table->decimal('attendance_percentage', 5)->nullable();
            $table->integer('class_rank')->nullable();
            $table->integer('total_students')->nullable();
            $table->json('special_achievements')->nullable();

            $table->index(['certificate_number'], 'idx_batch_certificates_number');
            $table->index(['user_id', 'is_revoked'], 'idx_batch_certificates_user_revoked');
            $table->unique(['user_id', 'batch_id'], 'unique_user_batch_certificate');
            $table->unique(['verification_code'], 'verification_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_certificates');
    }
};
