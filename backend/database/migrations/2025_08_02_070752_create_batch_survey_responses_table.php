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
        Schema::create('batch_survey_responses', function (Blueprint $table) {
            $table->char('response_id', 36)->primary();
            $table->char('survey_id', 36);
            $table->char('user_id', 36);
            $table->timestamp('submitted_at')->useCurrent();
            $table->json('responses');
            $table->boolean('is_anonymous')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_survey_responses');
    }
};