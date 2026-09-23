<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('protocol_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique(); // e.g. PR-1042, generated in the model

            // Which type of request this is, and a polymorphic link to the
            // type-specific table (protocol_guest_houses / protocol_vehicle_passes / protocol_tickets)
            $table->string('request_type'); // guesthouse | vehicle | ticket
            $table->unsignedBigInteger('requestable_id');
            $table->string('requestable_type');

            // Who raised it
            $table->unsignedBigInteger('employee_id');
            $table->string('employee_department')->nullable();

            // Workflow state
            $table->string('status')->default('pending'); // pending | recommended | approved | rejected
            $table->string('current_stage')->nullable(); // human-readable "what's happening now"

            // Protocol Staff who is/was handling this request
            $table->unsignedBigInteger('protocol_staff_id')->nullable();

            // Filled in only when status = recommended
            $table->unsignedBigInteger('recommended_to_id')->nullable();
            $table->timestamp('recommended_at')->nullable();

            // Filled in when the request reaches a final state
            $table->unsignedBigInteger('decided_by_id')->nullable();
            $table->timestamp('decided_at')->nullable();

            $table->text('purpose')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['request_type', 'status']);
            $table->index('employee_id');
            $table->index('protocol_staff_id');
            $table->index('recommended_to_id');
            $table->index(['requestable_id', 'requestable_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('protocol_requests');
    }
};
