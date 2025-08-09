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
        Schema::table('batch_messages', function (Blueprint $table) {
            $table->foreign(['batch_id'], 'batch_messages_ibfk_1')->references(['batch_id'])->on('course_batches')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['sender_id'], 'batch_messages_ibfk_2')->references(['user_id'])->on('users')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['recipient_id'], 'batch_messages_ibfk_3')->references(['user_id'])->on('users')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['parent_message_id'], 'batch_messages_ibfk_4')->references(['message_id'])->on('batch_messages')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('batch_messages', function (Blueprint $table) {
            $table->dropForeign('batch_messages_ibfk_1');
            $table->dropForeign('batch_messages_ibfk_2');
            $table->dropForeign('batch_messages_ibfk_3');
            $table->dropForeign('batch_messages_ibfk_4');
        });
    }
};
