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
        Schema::create('certificates', function (Blueprint $table) {
            $table->char('template_id', 36)->primary();
            $table->string('template_name', 255);
            $table->text('background_image_url')->nullable();
            $table->json('fields')->nullable();
            $table->enum('orientation', ['landscape', 'portrait'])->default('landscape');
            $table->enum('paper_size', ['A4', 'Letter', 'Legal'])->default('A4');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
