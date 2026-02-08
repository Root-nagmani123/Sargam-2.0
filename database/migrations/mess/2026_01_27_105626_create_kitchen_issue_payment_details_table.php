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
        Schema::create('kitchen_issue_payment_details', function (Blueprint $table) {
            $table->id('pk');
            $table->unsignedBigInteger('kitchen_issue_master_pk')->comment('Kitchen Issue Master FK');
            $table->string('invoice_no')->nullable()->comment('Invoice Number');
            $table->decimal('paid_amount', 10, 2)->default(0)->comment('Paid Amount');
            $table->date('payment_date')->nullable()->comment('Payment Date');
            $table->tinyInteger('payment_mode')->default(0)->comment('0=Cash, 1=Online, 2=Cheque');
            $table->string('transaction_ref')->nullable()->comment('Transaction Reference');
            $table->text('remarks')->nullable();
            $table->timestamps();

            // Foreign key
            $table->foreign('kitchen_issue_master_pk')
                  ->references('pk')
                  ->on('kitchen_issue_master')
                  ->onDelete('cascade');

            // Indexes
            $table->index('kitchen_issue_master_pk');
            $table->index('invoice_no');
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
        Schema::dropIfExists('kitchen_issue_payment_details');
    }
};
