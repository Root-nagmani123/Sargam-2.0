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
        Schema::create('estate_area_master', function (Blueprint $table) {
            $table->id('pk');
            $table->unsignedBigInteger('estate_campus_master_pk');
            $table->string('area_name', 255);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_date')->nullable();
            $table->unsignedBigInteger('modify_by')->nullable();
            $table->timestamp('modify_date')->nullable();
            $table->timestamps();
            
            $table->foreign('estate_campus_master_pk')
                  ->references('pk')
                  ->on('estate_campus_master')
                  ->onDelete('cascade');
            
            $table->index('area_name');
            $table->index('estate_campus_master_pk');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estate_area_master');
    }
};
