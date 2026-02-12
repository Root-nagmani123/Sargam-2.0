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
        Schema::create('issue_log_status', function (Blueprint $table) {
            $table->id('pk');
            $table->unsignedBigInteger('issue_log_management_pk');
            $table->timestamp('issue_date')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->tinyInteger('issue_status')->nullable()->comment('Status value at this point');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('assign_to')->nullable()->comment('Employee assigned to');
            
            // Foreign Keys
            $table->foreign('issue_log_management_pk')
                  ->references('pk')
                  ->on('issue_log_management')
                  ->onDelete('cascade');
            
            // Indexes
            $table->index('issue_log_management_pk');
            $table->index('created_by');
            $table->index('issue_status');
            $table->index('issue_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('issue_log_status');
    }
};
