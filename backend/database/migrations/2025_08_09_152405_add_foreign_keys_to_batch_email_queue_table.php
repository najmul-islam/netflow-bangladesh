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
        Schema::table('batch_email_queue', function (Blueprint $table) {
            $table->foreign(['batch_id'], 'batch_email_queue_ibfk_1')->references(['batch_id'])->on('course_batches')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['recipient_user_id'], 'batch_email_queue_ibfk_2')->references(['user_id'])->on('users')->onUpdate('restrict')->onDelete('set null');
            $table->foreign(['template_id'], 'batch_email_queue_ibfk_3')->references(['template_id'])->on('batch_notification_templates')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('batch_email_queue', function (Blueprint $table) {
            $table->dropForeign('batch_email_queue_ibfk_1');
            $table->dropForeign('batch_email_queue_ibfk_2');
            $table->dropForeign('batch_email_queue_ibfk_3');
        });
    }
};
