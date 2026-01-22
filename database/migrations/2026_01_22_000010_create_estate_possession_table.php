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
        Schema::create('estate_possession', function (Blueprint $table) {
            $table->id('pk');
            $table->unsignedBigInteger('estate_unit_master_pk');
            $table->unsignedBigInteger('employee_master_pk');
            $table->date('possession_date');
            $table->date('handover_date')->nullable();
            $table->string('meter_number', 100)->nullable();
            $table->integer('initial_meter_reading')->default(0);
            $table->integer('final_meter_reading')->nullable();
            $table->enum('possession_type', ['staff', 'officer', 'other'])->default('staff');
            $table->text('remarks')->nullable();
            $table->enum('status', ['active', 'inactive', 'vacated'])->default('active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_date')->nullable();
            $table->unsignedBigInteger('modify_by')->nullable();
            $table->timestamp('modify_date')->nullable();
            $table->timestamps();
            
            $table->foreign('estate_unit_master_pk')
                  ->references('pk')
                  ->on('estate_unit_master')
                  ->onDelete('cascade');
            
            $table->index('status');
            $table->index('possession_date');
            $table->index('employee_master_pk');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estate_possession');
    }
};
