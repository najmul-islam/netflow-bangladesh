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
        Schema::create('batch_statistics', function (Blueprint $table) {
            $table->char('batch_id', 36)->primary();
            $table->integer('total_enrollments')->nullable()->default(0);
            $table->integer('active_enrollments')->nullable()->default(0);
            $table->integer('completed_enrollments')->nullable()->default(0);
            $table->integer('dropped_enrollments')->nullable()->default(0);
            $table->decimal('completion_rate', 5)->nullable()->default(0);
            $table->decimal('average_rating', 3)->nullable()->default(0);
            $table->integer('total_ratings')->nullable()->default(0);
            $table->decimal('average_attendance', 5)->nullable()->default(0);
            $table->integer('total_classes_held')->nullable()->default(0);
            $table->integer('certificates_issued')->nullable()->default(0);
            $table->decimal('average_final_score', 5)->nullable()->default(0);
            $table->integer('forum_posts_count')->nullable()->default(0);
            $table->integer('total_assignments')->nullable()->default(0);
            $table->integer('submitted_assignments')->nullable()->default(0);
            $table->timestamp('last_updated')->useCurrentOnUpdate()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_statistics');
    }
};
