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
        Schema::create('courses', function (Blueprint $table) {
            $table->char('course_id', 36)->default('uuid()')->primary();
            $table->string('title');
            $table->string('slug')->unique('slug');
            $table->text('short_description')->nullable();
            $table->text('description')->nullable();
            $table->text('thumbnail_url')->nullable();
            $table->text('trailer_video_url')->nullable();
            $table->integer('category_id')->nullable()->index('idx_courses_category');
            $table->enum('difficulty_level', ['beginner', 'intermediate', 'advanced'])->nullable()->default('beginner');
            $table->decimal('estimated_duration_hours', 5)->nullable();
            $table->string('language', 10)->nullable()->default('en');
            $table->decimal('price', 10)->nullable()->default(0)->index('idx_courses_price');
            $table->string('currency', 3)->nullable()->default('USD');
            $table->boolean('is_free')->nullable()->default(true);
            $table->integer('max_enrollments')->nullable();
            $table->text('prerequisites')->nullable();
            $table->json('learning_objectives')->nullable();
            $table->enum('status', ['draft', 'published', 'archived', 'suspended'])->nullable()->default('draft')->index('idx_courses_status');
            $table->boolean('featured')->nullable()->default(false)->index('idx_courses_featured');
            $table->boolean('has_certificate')->nullable()->default(false);
            $table->char('certificate_template_id', 36)->nullable();
            $table->boolean('enable_batches')->nullable()->default(true)->index('idx_courses_batches_enabled');
            $table->integer('max_batch_size')->nullable()->default(50);
            $table->integer('min_batch_size')->nullable()->default(5);
            $table->boolean('auto_create_batches')->nullable()->default(false);
            $table->enum('batch_creation_criteria', ['enrollment_order', 'manual', 'date_based', 'capacity_based'])->nullable()->default('manual');
            $table->integer('batch_start_interval_days')->nullable()->default(30);
            $table->char('created_by', 36)->index('idx_courses_created_by');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->timestamp('published_at')->nullable()->index('idx_courses_published_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
