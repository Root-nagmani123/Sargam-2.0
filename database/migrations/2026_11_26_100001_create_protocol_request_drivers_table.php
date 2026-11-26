<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('protocol_request_drivers')) {
            return;
        }

        if (! Schema::hasTable('protocol_requests')) {
            return;
        }

        Schema::create('protocol_request_drivers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('protocol_request_id');
            $table->string('vehicle_no', 100);
            $table->string('vehicle_name', 150);
            $table->string('driver_name', 150);
            $table->string('driver_number', 30);
            $table->timestamps();

            $table->unique(
                'protocol_request_id',
                'uq_protocol_request_drivers_request'
            );
            $table->foreign(
                'protocol_request_id',
                'fk_protocol_request_drivers_request'
            )->references('id')
                ->on('protocol_requests')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('protocol_request_drivers');
    }
};
