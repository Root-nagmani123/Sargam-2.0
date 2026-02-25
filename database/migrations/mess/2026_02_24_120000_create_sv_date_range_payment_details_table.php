<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Payment details for Selling Voucher Date Range reports (process mess bills - partial/full payment).
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sv_date_range_payment_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sv_date_range_report_id')->comment('Selling Voucher Date Range Report FK');
            $table->decimal('paid_amount', 12, 2)->default(0)->comment('Paid Amount');
            $table->date('payment_date')->nullable()->comment('Payment Date');
            $table->string('payment_mode', 50)->nullable()->comment('cash, cheque, online, deduct_from_salary');
            $table->string('bank_name')->nullable();
            $table->string('cheque_number')->nullable();
            $table->date('cheque_date')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('sv_date_range_report_id')
                ->references('id')
                ->on('sv_date_range_reports')
                ->onDelete('cascade');
            $table->index('sv_date_range_report_id');
            $table->index('payment_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sv_date_range_payment_details');
    }
};
