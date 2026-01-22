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
        Schema::create('estate_request_details', function (Blueprint $table) {
            $table->id('pk');
            $table->unsignedBigInteger('employee_master_pk');
            $table->unsignedBigInteger('estate_unit_type_master_pk');
            $table->date('request_date');
            $table->enum('request_type', ['new', 'change', 'extension'])->default('new');
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'forwarded'])->default('pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->date('approved_date')->nullable();
            $table->text('approval_remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_date')->nullable();
            $table->unsignedBigInteger('modify_by')->nullable();
            $table->timestamp('modify_date')->nullable();
            $table->timestamps();
            
            $table->foreign('estate_unit_type_master_pk')
                  ->references('pk')
                  ->on('estate_unit_type_master')
                  ->onDelete('cascade');
            
            $table->index('status');
            $table->index('request_type');
            $table->index('employee_master_pk');
            $table->index('request_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estate_request_details');
    }
};
