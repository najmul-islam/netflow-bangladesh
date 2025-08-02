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
        Schema::create('batch_schedule_resources', function (Blueprint $table) {
            $table->char('resource_id', 36)->primary();
            $table->char('schedule_id', 36);
            $table->string('title', 255);
            $table->text('url');
            $table->enum('resource_type', ['link', 'document', 'video', 'external_tool'])->default('link');
            $table->boolean('is_required')->default(false);
            $table->json('metadata')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_schedule_resources');
    }
};