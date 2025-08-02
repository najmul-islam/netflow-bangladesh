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
            $table->char('review_id', 36)->primary();
            $table->char('batch_id', 36);
            $table->char('user_id', 36);
            $table->integer('rating')->check('rating >= 1 and rating <= 5');
            $table->string('title', 255)->nullable();
            $table->text('content')->nullable();
            $table->json('review_categories')->nullable();
            $table->boolean('is_approved')->default(true);
            $table->boolean('is_anonymous')->default(false);
            $table->text('instructor_response')->nullable();
            $table->timestamp('instructor_responded_at')->nullable();
            $table->integer('helpful_count')->default(0);
            $table->timestamps();
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