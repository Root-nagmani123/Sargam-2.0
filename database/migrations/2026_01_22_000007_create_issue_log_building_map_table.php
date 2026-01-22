<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('issue_log_building_map', function (Blueprint $table) {
            $table->id('pk');
            $table->unsignedBigInteger('issue_log_management_pk');
            $table->unsignedBigInteger('building_master_pk')->nullable();
            $table->string('floor_name', 100)->nullable();
            $table->string('room_name', 100)->nullable();
            
            // Foreign Keys
            $table->foreign('issue_log_management_pk')
                  ->references('pk')
                  ->on('issue_log_management')
                  ->onDelete('cascade');
            
            // Building master foreign key - conditional as table may not exist
            if (Schema::hasTable('building_master')) {
                $table->foreign('building_master_pk')
                      ->references('pk')
                      ->on('building_master')
                      ->onDelete('set null');
            }
            
            // Indexes
            $table->index('issue_log_management_pk');
            $table->index('building_master_pk');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('issue_log_building_map');
    }
};
