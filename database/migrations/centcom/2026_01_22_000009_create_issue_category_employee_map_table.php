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
        Schema::create('issue_category_employee_map', function (Blueprint $table) {
            $table->id('pk');
            $table->unsignedBigInteger('employee_master_pk')->nullable();
            $table->unsignedBigInteger('issue_category_master_pk');
            $table->timestamp('created_date')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->integer('days_notify')->nullable()->comment('Days for notification/escalation');
            $table->tinyInteger('priority')->nullable()->comment('Priority level for escalation (1-4)');
            
            // Foreign Keys
            $table->foreign('issue_category_master_pk')
                  ->references('pk')
                  ->on('issue_category_master')
                  ->onDelete('cascade');
            
            // Employee master foreign key
            if (Schema::hasTable('employee_master')) {
                $table->foreign('employee_master_pk')
                      ->references('pk')
                      ->on('employee_master')
                      ->onDelete('cascade');
            }
            
            // Indexes
            $table->index('employee_master_pk');
            $table->index('issue_category_master_pk');
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('issue_category_employee_map');
    }
};
