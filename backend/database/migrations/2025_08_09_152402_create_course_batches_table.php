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
        Schema::create('course_batches', function (Blueprint $table) {
            $table->char('batch_id', 36)->default('uuid()')->primary();
            $table->char('course_id', 36)->index('idx_batches_course');
            $table->string('batch_name');
            $table->string('batch_code', 50);
            $table->text('description')->nullable();
            $table->integer('max_students')->nullable()->default(50);
            $table->integer('current_students')->nullable()->default(0);
            $table->date('start_date')->index('idx_batches_start_date');
            $table->date('end_date')->nullable();
            $table->timestamp('enrollment_start_date')->nullable();
            $table->timestamp('enrollment_end_date')->nullable();
            $table->enum('status', ['draft', 'open_for_enrollment', 'in_progress', 'completed', 'cancelled', 'suspended'])->nullable()->default('draft')->index('idx_batches_status');
            $table->enum('batch_type', ['regular', 'fast_track', 'weekend', 'evening', 'custom'])->nullable()->default('regular');
            $table->string('timezone', 50)->nullable()->default('UTC');
            $table->boolean('is_featured')->nullable()->default(false)->index('idx_batches_featured');
            $table->boolean('auto_generated')->nullable()->default(false);
            $table->text('notes')->nullable();
            $table->char('created_by', 36)->index('created_by');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();

            $table->fullText(['batch_name', 'description'], 'batch_name');
            $table->index(['enrollment_start_date', 'enrollment_end_date'], 'idx_batches_enrollment_dates');
            $table->unique(['course_id', 'batch_code'], 'unique_course_batch_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_batches');
    }
};
