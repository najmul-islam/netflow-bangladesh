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
        Schema::create('batch_student_performance', function (Blueprint $table) {
            $table->char('performance_id', 36)->default('uuid()')->primary();
            $table->char('user_id', 36)->index('idx_performance_user');
            $table->char('batch_id', 36)->index('idx_performance_batch');
            $table->integer('total_classes')->nullable()->default(0);
            $table->integer('attended_classes')->nullable()->default(0);
            $table->decimal('attendance_percentage', 5)->nullable()->default(0);
            $table->integer('assignments_submitted')->nullable()->default(0);
            $table->integer('assignments_total')->nullable()->default(0);
            $table->decimal('average_assignment_score', 5)->nullable()->default(0);
            $table->integer('quiz_attempts')->nullable()->default(0);
            $table->decimal('average_quiz_score', 5)->nullable()->default(0);
            $table->decimal('final_exam_score', 5)->nullable();
            $table->decimal('overall_grade', 5)->nullable()->index('idx_performance_grade');
            $table->enum('grade_letter', ['A+', 'A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D', 'F'])->nullable();
            $table->integer('class_rank')->nullable();
            $table->decimal('participation_score', 5)->nullable()->default(0);
            $table->timestamp('last_updated')->useCurrentOnUpdate()->useCurrent();

            $table->unique(['user_id', 'batch_id'], 'unique_user_batch_performance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_student_performance');
    }
};
