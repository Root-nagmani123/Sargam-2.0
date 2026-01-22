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
        Schema::create('estate_unit_master', function (Blueprint $table) {
            $table->id('pk');
            $table->unsignedBigInteger('estate_campus_master_pk');
            $table->unsignedBigInteger('estate_area_master_pk');
            $table->unsignedBigInteger('estate_block_master_pk');
            $table->unsignedBigInteger('estate_unit_type_master_pk');
            $table->unsignedBigInteger('estate_unit_sub_type_master_pk')->nullable();
            $table->string('unit_name', 255);
            $table->string('house_address', 500)->nullable();
            $table->text('description')->nullable();
            $table->integer('capacity')->default(0);
            $table->decimal('estate_value', 15, 2)->nullable();
            $table->decimal('rent', 15, 2)->nullable();
            $table->tinyInteger('is_rent_applicable')->default(1);
            $table->integer('quantity')->default(1);
            $table->unsignedBigInteger('facility_master_pk')->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_date')->nullable();
            $table->unsignedBigInteger('modify_by')->nullable();
            $table->timestamp('modify_date')->nullable();
            $table->timestamps();
            
            $table->foreign('estate_campus_master_pk')
                  ->references('pk')
                  ->on('estate_campus_master')
                  ->onDelete('cascade');
                  
            $table->foreign('estate_area_master_pk')
                  ->references('pk')
                  ->on('estate_area_master')
                  ->onDelete('cascade');
                  
            $table->foreign('estate_block_master_pk')
                  ->references('pk')
                  ->on('estate_block_master')
                  ->onDelete('cascade');
                  
            $table->foreign('estate_unit_type_master_pk')
                  ->references('pk')
                  ->on('estate_unit_type_master')
                  ->onDelete('cascade');
                  
            $table->foreign('estate_unit_sub_type_master_pk')
                  ->references('pk')
                  ->on('estate_unit_sub_type_master')
                  ->onDelete('set null');
            
            $table->index('unit_name');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estate_unit_master');
    }
};
