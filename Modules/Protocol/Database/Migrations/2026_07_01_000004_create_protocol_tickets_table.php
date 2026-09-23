<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('protocol_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('mode'); // Rail | Air | Bus
            $table->string('from_place');
            $table->string('to_place');
            $table->date('journey_date');
            $table->string('travel_class')->nullable(); // e.g. AC 2-Tier, Economy
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('protocol_tickets');
    }
};
