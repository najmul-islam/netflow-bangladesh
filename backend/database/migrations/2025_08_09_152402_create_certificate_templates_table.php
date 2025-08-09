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
        Schema::create('certificate_templates', function (Blueprint $table) {
            $table->char('template_id', 36)->default('uuid()')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('template_html');
            $table->text('template_css')->nullable();
            $table->json('variables')->nullable();
            $table->boolean('is_active')->nullable()->default(true);
            $table->boolean('is_batch_specific')->nullable()->default(false);
            $table->char('created_by', 36)->index('created_by');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificate_templates');
    }
};
