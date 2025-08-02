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
        Schema::create('discussion_forums', function (Blueprint $table) {
            $table->char('forum_id', 36)->primary();
            $table->char('batch_id', 36);
            $table->string('forum_title', 255);
            $table->text('forum_description')->nullable();
            $table->enum('forum_type', ['general', 'course_specific', 'assessment_related'])->default('general');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discussion_forums');
    }
};
