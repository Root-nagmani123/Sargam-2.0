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
        Schema::create('kitchen_issue_master', function (Blueprint $table) {
            $table->id('pk');
            $table->unsignedBigInteger('inve_item_master_pk')->comment('Item Master FK');
            $table->unsignedBigInteger('inve_store_master_pk')->comment('Store/Mess Master FK');
            $table->unsignedBigInteger('requested_store_id')->nullable()->comment('Requested Store ID for transfer');
            $table->decimal('quantity', 10, 2)->default(0)->comment('Item Quantity');
            $table->unsignedBigInteger('user_id')->comment('Created by user');
            $table->tinyInteger('status')->default(0)->comment('0=Pending, 1=Processing, 2=Approved, 3=Rejected, 4=Completed');
            $table->unsignedBigInteger('store_employee_master_pk')->nullable()->comment('Store Employee FK');
            $table->dateTime('request_date')->useCurrent()->comment('Request Date');
            $table->decimal('unit_price', 10, 2)->default(0)->comment('Unit Price');
            $table->tinyInteger('payment_type')->default(0)->comment('0=Cash, 1=Credit, 2=Debit, 5=Account');
            $table->date('issue_date')->nullable()->comment('Issue Date');
            $table->tinyInteger('transfer_to')->default(0)->comment('Transfer Type');
            $table->tinyInteger('client_type')->default(0)->comment('Client Type: 2=Student, 5=Employee, Others');
            $table->unsignedBigInteger('client_type_pk')->nullable()->comment('FK to mess_customer_type');
            $table->string('client_name')->nullable()->comment('Client Name');
            $table->unsignedBigInteger('employee_student_pk')->default(0)->comment('Employee/Student FK');
            $table->string('bill_no')->nullable()->comment('Bill Number');
            $table->tinyInteger('send_for_approval')->default(0)->comment('0=Not Sent, 1=Sent for Approval');
            $table->tinyInteger('notify_status')->default(0)->comment('0=Not Notified, 1=Notified');
            $table->tinyInteger('approve_status')->default(0)->comment('0=Pending Approval, 1=Approved, 2=Rejected');
            $table->tinyInteger('paid_unpaid')->default(0)->comment('0=Unpaid, 1=Paid');
            $table->text('remarks')->nullable()->comment('Remarks');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('inve_item_master_pk');
            $table->index('inve_store_master_pk');
            $table->index('employee_student_pk');
            $table->index('client_type_pk');
            $table->index('status');
            $table->index('payment_type');
            $table->index('approve_status');
            $table->index('request_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('kitchen_issue_master');
    }
};
