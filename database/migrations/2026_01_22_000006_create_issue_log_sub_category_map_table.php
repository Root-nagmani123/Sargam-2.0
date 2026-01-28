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
        Schema::create('issue_log_sub_category_map', function (Blueprint $table) {
            $table->id('pk');
            $table->unsignedBigInteger('issue_log_management_pk');
            $table->unsignedBigInteger('issue_category_master_pk')->nullable();
            $table->unsignedBigInteger('issue_sub_category_master_pk');
            $table->string('sub_category_name', 255)->nullable()->comment('Denormalized for quick access');
            
            // Foreign Keys
            $table->foreign('issue_log_management_pk')
                  ->references('pk')
                  ->on('issue_log_management')
                  ->onDelete('cascade');
            
            $table->foreign('issue_category_master_pk')
                  ->references('pk')
                  ->on('issue_category_master')
                  ->onDelete('set null');
            
            $table->foreign('issue_sub_category_master_pk')
                  ->references('pk')
                  ->on('issue_sub_category_master')
                  ->onDelete('cascade');
            
            // Indexes
            $table->index('issue_log_management_pk');
            $table->index('issue_category_master_pk');
            $table->index('issue_sub_category_master_pk');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('issue_log_sub_category_map');
    }
};
