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
        Schema::create('sec_visitor_card_generated', function (Blueprint $table) {
            $table->id('pk');
            $table->integer('pass_number')->nullable();
            $table->string('vehicle_number', 50)->nullable();
            $table->string('vehicle_pass_number', 50)->nullable();
            $table->string('company', 255)->nullable();
            $table->text('address')->nullable();
            $table->unsignedBigInteger('employee_master_pk')->nullable();
            $table->text('purpose')->nullable();
            $table->dateTime('in_time')->nullable();
            $table->dateTime('out_time')->nullable();
            $table->string('upload_path', 255)->nullable();
            $table->string('mobile_number', 20)->nullable();
            $table->string('identity_card', 100)->nullable();
            $table->integer('valid_for_days')->default(1);
            $table->date('issued_date')->nullable();
            $table->string('id_no', 50)->nullable();
            $table->timestamp('created_date')->nullable();
            $table->timestamp('modified_date')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            
            $table->foreign('employee_master_pk')->references('pk')->on('employee_master')->onDelete('set null');
            $table->foreign('created_by')->references('pk')->on('employee_master')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sec_visitor_card_generated');
    }
};
