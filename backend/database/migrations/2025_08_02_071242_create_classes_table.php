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
        Schema::create('classes', function (Blueprint $table) {
            $table->char('class_id', 36)->primary();
            $table->char('batch_id', 36);
            $table->string('class_name', 255);
            $table->text('description')->nullable();
            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->char('instructor_id', 36);
            $table->string('location', 255)->nullable();
            $table->enum('class_type', ['in_person', 'virtual'])->default('virtual');
            $table->string('meeting_link', 500)->nullable();
            $table->json('materials')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
