<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gtc_session_confirmations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('course_id', 64);
            $table->string('faculty_id', 64);
            $table->date('period_from');
            $table->date('period_to');
            $table->char('session_fingerprint', 64);
            $table->json('sessions');
            $table->timestamp('confirmed_at');
            $table->timestamps();
            $table->unique(['user_id', 'session_fingerprint'], 'gtc_session_confirmations_user_fingerprint_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gtc_session_confirmations');
    }
};
