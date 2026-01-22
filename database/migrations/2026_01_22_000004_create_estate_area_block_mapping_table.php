<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('estate_area_block_mapping', function (Blueprint $table) {
            $table->id('pk');
            $table->unsignedBigInteger('estate_area_master_pk');
            $table->unsignedBigInteger('estate_block_master_pk');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_date')->nullable();
            $table->unsignedBigInteger('modify_by')->nullable();
            $table->timestamp('modify_date')->nullable();
            $table->timestamps();
            
            $table->foreign('estate_area_master_pk')
                  ->references('pk')
                  ->on('estate_area_master')
                  ->onDelete('cascade');
                  
            $table->foreign('estate_block_master_pk')
                  ->references('pk')
                  ->on('estate_block_master')
                  ->onDelete('cascade');
            
            $table->index(['estate_area_master_pk', 'estate_block_master_pk'], 'area_block_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estate_area_block_mapping');
    }
};
