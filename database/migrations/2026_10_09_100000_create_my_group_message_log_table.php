<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Durable audit of My Groups SMS / email sends (PR #334 F-005).
 *
 * One row per send: who sent it, to which group, on which channel, how many
 * recipients and how many the gateway accepted. The message text is deliberately
 * not stored — it is the sender's content, and the row exists to attribute and
 * count sends, not to archive them. A log line alone rotated away after 14 days.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('my_group_message_log')) {
            return;
        }

        Schema::create('my_group_message_log', function (Blueprint $table) {
            $table->bigIncrements('pk');
            $table->unsignedBigInteger('sender_user_pk')->index();
            $table->unsignedBigInteger('sender_student_pk')->nullable();
            $table->unsignedBigInteger('group_map_pk')->index();
            $table->string('channel', 10);
            $table->unsignedInteger('recipient_count');
            $table->unsignedInteger('sent_count');
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('my_group_message_log');
    }
};
