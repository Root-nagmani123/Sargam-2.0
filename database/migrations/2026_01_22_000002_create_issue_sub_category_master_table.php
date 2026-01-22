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
        Schema::create('issue_sub_category_master', function (Blueprint $table) {
            $table->id('pk');
            $table->unsignedBigInteger('issue_category_master_pk');
            $table->string('issue_sub_category', 255)->nullable(false);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_date')->useCurrent();
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->timestamp('modified_date')->nullable();
            $table->tinyInteger('status')->default(1)->comment('1=Active, 0=Inactive');
            
            // Foreign Keys
            $table->foreign('issue_category_master_pk')
                  ->references('pk')
                  ->on('issue_category_master')
                  ->onDelete('cascade');
            
            // Indexes
            $table->index('issue_category_master_pk');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('issue_sub_category_master');
    }
};
