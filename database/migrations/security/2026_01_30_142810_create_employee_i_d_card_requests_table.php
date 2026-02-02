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
        Schema::create('employee_i_d_card_requests', function (Blueprint $table) {
            $table->id();
            $table->enum('employee_type', ['Permanent Employee', 'Contractual Employee'])->default('Permanent Employee');
            $table->string('card_type')->nullable();
            $table->string('sub_type')->nullable();
            $table->string('request_for')->nullable();
            $table->string('name')->nullable();
            $table->string('designation')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('father_name')->nullable();
            $table->date('academy_joining')->nullable();
            $table->string('id_card_valid_upto')->nullable();
            $table->string('mobile_number')->nullable();
            $table->string('telephone_number')->nullable();
            $table->string('blood_group')->nullable();
            $table->string('section')->nullable();
            $table->string('approval_authority')->nullable();
            $table->string('vendor_organization_name')->nullable();
            $table->string('photo')->nullable(); // file path
            $table->string('documents')->nullable(); // file path
            $table->enum('status', ['Pending', 'Approved', 'Rejected', 'Issued'])->default('Pending');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_i_d_card_requests');
    }
};
