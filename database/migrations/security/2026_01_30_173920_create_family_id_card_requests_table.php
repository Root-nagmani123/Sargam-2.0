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
        Schema::createIfNotExists('family_id_card_requests', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id')->nullable()->comment('Employee ID e.g. ITS005');
            $table->string('employee_name')->nullable()->comment('Employee name for display');
            $table->string('designation')->nullable();
            $table->string('card_type')->nullable()->default('Family');
            $table->string('name')->nullable()->comment('Family member name');
            $table->string('section')->nullable();
            $table->string('family_photo')->nullable()->comment('Uploaded family photo path');
            $table->date('dob')->nullable()->comment('Family member date of birth');
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->string('family_member_id')->nullable()->comment('Issued family member ID card number');
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
        Schema::dropIfExists('family_id_card_requests');
    }
};
