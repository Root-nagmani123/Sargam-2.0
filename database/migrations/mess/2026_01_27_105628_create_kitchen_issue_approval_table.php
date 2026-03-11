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
        Schema::create('kitchen_issue_approval', function (Blueprint $table) {
            $table->id('pk');
            $table->unsignedBigInteger('kitchen_issue_master_pk')->comment('Kitchen Issue Master FK');
            $table->unsignedBigInteger('approver_id')->comment('Approver Employee FK');
            $table->tinyInteger('approval_level')->default(1)->comment('Approval Level');
            $table->tinyInteger('status')->default(0)->comment('0=Pending, 1=Approved, 2=Rejected');
            $table->text('remarks')->nullable()->comment('Approval Remarks');
            $table->dateTime('approved_date')->nullable()->comment('Approval Date');
            $table->timestamps();

            // Foreign key
            $table->foreign('kitchen_issue_master_pk')
                  ->references('pk')
                  ->on('kitchen_issue_master')
                  ->onDelete('cascade');

            // Indexes
            $table->index('kitchen_issue_master_pk');
            $table->index('approver_id');
            $table->index('status');
            $table->index('approval_level');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('kitchen_issue_approval');
    }
};
