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
        Schema::create('user_activities', function (Blueprint $table) {
            $table->char('activity_id', 36)->primary();
            $table->char('user_id', 36);
            $table->enum('activity_type', [
                'login',
                'logout',
                'view_lesson',
                'submit_assignment',
                'attempt_quiz',
                'download_material',
                'post_forum',
                'update_profile'
            ]);
            $table->text('description')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('activity_time')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_activities');
    }
};
