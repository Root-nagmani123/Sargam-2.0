<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates sec_vehcl_pass_config if it does not exist (required for Vehicle Pass request ID generation).
     */
    public function up(): void
    {
        if (Schema::hasTable('sec_vehcl_pass_config')) {
            return;
        }

        Schema::create('sec_vehcl_pass_config', function (Blueprint $table) {
            $table->id('pk');
            $table->unsignedBigInteger('sec_vehicle_type_pk');
            $table->decimal('charges', 10, 2)->default(0);
            $table->integer('start_counter')->default(1);
            $table->tinyInteger('active_inactive')->default(1);
            $table->timestamp('created_date')->nullable();
            $table->timestamp('modified_date')->nullable();
        });

        if (Schema::hasTable('sec_vehicle_type')) {
            Schema::table('sec_vehcl_pass_config', function (Blueprint $table) {
                $table->foreign('sec_vehicle_type_pk')->references('pk')->on('sec_vehicle_type')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sec_vehcl_pass_config');
    }
};
