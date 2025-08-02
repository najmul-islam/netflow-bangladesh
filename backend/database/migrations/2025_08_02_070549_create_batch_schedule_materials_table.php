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
        Schema::create('batch_schedule_materials', function (Blueprint $table) {
            $table->char('material_id', 36)->primary();
            $table->char('schedule_id', 36);
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->enum('material_type', ['pdf', 'doc', 'ppt', 'video', 'link', 'quiz', 'assignment'])->default('pdf');
            $table->text('file_url')->nullable();
            $table->json('tags')->nullable();
            $table->char('uploaded_by', 36);
            $table->timestamp('uploaded_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_schedule_materials');
    }
};