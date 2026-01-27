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
        Schema::create('issue_log_management_history', function (Blueprint $table) {
            $table->id('pk');
            $table->unsignedBigInteger('issue_log_management_pk');
            $table->unsignedBigInteger('escalated_about')->nullable()->comment('Employee from whom escalated');
            $table->unsignedBigInteger('escalated_to_employee_pk')->nullable()->comment('Employee to whom escalated');
            $table->tinyInteger('status')->default(0)->comment('Escalation status');
            $table->timestamp('assign_date')->nullable()->comment('Assignment/escalation date');
            $table->unsignedBigInteger('employee_pk_assign1')->nullable()->comment('Assigned employee');
            $table->tinyInteger('priority')->nullable()->comment('Priority level');
            $table->unsignedBigInteger('notify_level_1')->nullable()->comment('First level notification employee');
            $table->unsignedBigInteger('notify_level_2')->nullable()->comment('Second level notification employee');
            $table->unsignedBigInteger('notify_level_3')->nullable()->comment('Third level notification employee');
            $table->timestamp('notify_datetime')->nullable()->comment('Notification timestamp');
            
            // Foreign Keys
            $table->foreign('issue_log_management_pk')
                  ->references('pk')
                  ->on('issue_log_management')
                  ->onDelete('cascade');
            
            // Indexes
            $table->index('issue_log_management_pk');
            $table->index('escalated_about');
            $table->index('escalated_to_employee_pk');
            $table->index('employee_pk_assign1');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('issue_log_management_history');
    }
};
