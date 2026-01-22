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
        Schema::create('estate_warden_mapping', function (Blueprint $table) {
            $table->id('pk');
            $table->unsignedBigInteger('estate_campus_master_pk');
            $table->unsignedBigInteger('employee_master_pk');
            $table->unsignedBigInteger('department_master_pk')->nullable();
            $table->unsignedBigInteger('designation_master_pk')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_date')->nullable();
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->timestamp('modified_date')->nullable();
            $table->timestamps();
            
            $table->foreign('estate_campus_master_pk')
                  ->references('pk')
                  ->on('estate_campus_master')
                  ->onDelete('cascade');
            
            $table->index('estate_campus_master_pk');
            $table->index('employee_master_pk');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estate_warden_mapping');
    }
};
