<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('protocol_approval_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('protocol_request_id');
            $table->string('action'); // raised | recommended | approved | rejected
            $table->unsignedBigInteger('action_by_id'); // user who performed the action
            $table->string('action_by_role')->nullable(); // Employee | Protocol Staff | Manager (snapshot for display)
            $table->unsignedBigInteger('recommended_to_id')->nullable(); // only set when action = recommended
            $table->text('remarks')->nullable();
            $table->timestamp('action_at');
            $table->timestamps();

            $table->foreign('protocol_request_id')
                ->references('id')->on('protocol_requests')
                ->onDelete('cascade');

            $table->index(['protocol_request_id', 'action_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('protocol_approval_logs');
    }
};
