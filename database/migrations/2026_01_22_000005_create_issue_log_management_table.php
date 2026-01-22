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
        Schema::create('issue_log_management', function (Blueprint $table) {
            $table->id('pk');
            $table->unsignedBigInteger('issue_category_master_pk')->nullable();
            $table->unsignedBigInteger('issue_priority_master_pk')->nullable();
            $table->unsignedBigInteger('issue_reproducibility_master_pk')->nullable();
            $table->text('description')->nullable(false);
            $table->string('location', 500)->nullable();
            $table->string('document', 500)->nullable()->comment('Attached document path');
            $table->tinyInteger('issue_status')->default(0)->comment('0=Reported, 1=In Progress, 2=Completed, 3=Pending, 6=Reopened');
            $table->text('remark')->nullable();
            $table->unsignedBigInteger('created_by')->nullable(false);
            $table->timestamp('created_date')->useCurrent();
            $table->time('created_time')->nullable();
            $table->unsignedBigInteger('issue_logger')->nullable(false)->comment('Person who logged the issue');
            $table->tinyInteger('behalf')->default(1)->comment('0=On behalf (Centcom), 1=Self');
            $table->unsignedBigInteger('employee_master_pk')->nullable()->comment('Assigned employee');
            $table->string('assigned_to', 255)->nullable();
            $table->string('assigned_to_contact', 100)->nullable();
            $table->tinyInteger('notification_status')->default(0)->comment('0=Pending, 1=Notified');
            $table->text('feedback')->nullable();
            $table->tinyInteger('feedback_status')->nullable();
            $table->string('latitude', 50)->nullable()->comment('GPS latitude for mobile app');
            $table->string('longitude', 50)->nullable()->comment('GPS longitude for mobile app');
            $table->string('image_name', 500)->nullable()->comment('Issue image filename');
            $table->string('device_type', 50)->nullable()->comment('Android/iOS');
            $table->string('device_id', 255)->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('updated_date')->nullable();
            $table->timestamp('clear_date')->nullable()->comment('Issue resolution date');
            $table->time('clear_time')->nullable()->comment('Issue resolution time');
            
            // Foreign Keys
            $table->foreign('issue_category_master_pk')
                  ->references('pk')
                  ->on('issue_category_master')
                  ->onDelete('set null');
            
            $table->foreign('issue_priority_master_pk')
                  ->references('pk')
                  ->on('issue_priority_master')
                  ->onDelete('set null');
            
            $table->foreign('issue_reproducibility_master_pk')
                  ->references('pk')
                  ->on('issue_reproducibility_master')
                  ->onDelete('set null');
            
            // Indexes
            $table->index('issue_category_master_pk');
            $table->index('issue_priority_master_pk');
            $table->index('issue_reproducibility_master_pk');
            $table->index('created_by');
            $table->index('issue_logger');
            $table->index('employee_master_pk');
            $table->index('issue_status');
            $table->index('behalf');
            $table->index('created_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('issue_log_management');
    }
};
