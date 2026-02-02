<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates sec_vehicle_type if it does not exist (required for Vehicle Pass).
     */
    public function up(): void
    {
        if (Schema::hasTable('sec_vehicle_type')) {
            return;
        }

        Schema::create('sec_vehicle_type', function (Blueprint $table) {
            $table->id('pk');
            $table->string('vehicle_type', 100);
            $table->text('description')->nullable();
            $table->tinyInteger('active_inactive')->default(1);
            $table->timestamp('created_date')->nullable();
            $table->timestamp('modified_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sec_vehicle_type');
    }
};
