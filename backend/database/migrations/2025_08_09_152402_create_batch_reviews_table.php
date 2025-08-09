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
        Schema::create('batch_reviews', function (Blueprint $table) {
            $table->char('review_id', 36)->default('uuid()')->primary();
            $table->char('batch_id', 36)->index('idx_batch_reviews_batch');
            $table->char('user_id', 36);
            $table->integer('rating')->index('idx_batch_reviews_rating');
            $table->string('title')->nullable();
            $table->text('content')->nullable();
            $table->json('review_categories')->nullable();
            $table->boolean('is_approved')->nullable()->default(true)->index('idx_batch_reviews_approved');
            $table->boolean('is_anonymous')->nullable()->default(false);
            $table->text('instructor_response')->nullable();
            $table->timestamp('instructor_responded_at')->nullable();
            $table->integer('helpful_count')->nullable()->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();

            $table->unique(['user_id', 'batch_id'], 'unique_user_batch_review');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_reviews');
    }
};
