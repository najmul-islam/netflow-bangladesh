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
        Schema::table('batch_forum_topics', function (Blueprint $table) {
            $table->foreign(['forum_id'], 'batch_forum_topics_ibfk_1')->references(['forum_id'])->on('batch_forums')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['batch_id'], 'batch_forum_topics_ibfk_2')->references(['batch_id'])->on('course_batches')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['created_by'], 'batch_forum_topics_ibfk_3')->references(['user_id'])->on('users')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['last_reply_by'], 'batch_forum_topics_ibfk_4')->references(['user_id'])->on('users')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('batch_forum_topics', function (Blueprint $table) {
            $table->dropForeign('batch_forum_topics_ibfk_1');
            $table->dropForeign('batch_forum_topics_ibfk_2');
            $table->dropForeign('batch_forum_topics_ibfk_3');
            $table->dropForeign('batch_forum_topics_ibfk_4');
        });
    }
};
