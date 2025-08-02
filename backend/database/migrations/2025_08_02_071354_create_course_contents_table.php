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
        Schema::create('course_contents', function (Blueprint $table) {
            $table->char('content_id', 36)->primary();
            $table->char('course_id', 36);
            $table->char('lesson_id', 36);
            $table->enum('content_type', ['video', 'document', 'quiz', 'assignment', 'live_class'])->default('video');
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->text('url')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_contents');
    }
};
