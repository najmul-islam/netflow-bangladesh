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
        Schema::create('users', function (Blueprint $table) {
            $table->char('user_id', 36)->default('uuid()')->primary();
            $table->string('email')->unique('email');
            $table->string('username', 100)->unique('username');
            $table->string('password');
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('phone', 20)->nullable();
            $table->text('avatar_url')->nullable();
            $table->text('bio')->nullable();
            $table->string('timezone', 50)->nullable()->default('UTC');
            $table->string('language', 10)->nullable()->default('en');
            $table->enum('status', ['active', 'inactive', 'suspended'])->nullable()->default('active')->index('idx_users_status');
            $table->boolean('email_verified')->nullable()->default(false);
            $table->timestamp('last_login')->nullable();
            $table->timestamp('created_at')->useCurrent()->index('idx_users_created_at');
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();

            $table->index(['email'], 'idx_users_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
