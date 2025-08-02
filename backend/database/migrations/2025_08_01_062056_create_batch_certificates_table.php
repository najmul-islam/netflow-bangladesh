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
            $table->char('certificate_id', 36)->primary();
            $table->char('user_id', 36);
            $table->char('batch_id', 36);
            $table->char('template_id', 36);
            $table->string('certificate_number', 100);
            $table->timestamp('issued_date')->useCurrent();
            $table->timestamp('expiry_date')->nullable();
            $table->string('verification_code', 100);
            $table->text('certificate_url')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_revoked')->default(false);
            $table->timestamp('revoked_at')->nullable();
            $table->char('revoked_by', 36)->nullable();
            $table->text('revocation_reason')->nullable();
            $table->date('batch_completion_date')->nullable();
            $table->decimal('final_grade', 5, 2)->nullable();
            $table->decimal('attendance_percentage', 5, 2)->nullable();
            $table->integer('class_rank')->nullable();
            $table->integer('total_students')->nullable();
            $table->json('special_achievements')->nullable();
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