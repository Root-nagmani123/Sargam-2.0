<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('protocol_vehicle_passes', function (Blueprint $table) {
            $table->id();
            $table->string('from_location');
            $table->string('to_location');
            $table->date('date_of_travel');
            $table->time('travel_time')->nullable();
            $table->string('preferred_vehicle')->nullable(); // e.g. Innova, Bus (32-seater), Any available
            $table->unsignedInteger('vehicle_master_id')->nullable(); // FK to a fleet masters table if maintained
            $table->unsignedInteger('no_of_passengers')->default(1);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('protocol_vehicle_passes');
    }
};
