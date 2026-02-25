<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('mess_inbound_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number')->unique();
            $table->unsignedBigInteger('purchase_order_id')->nullable();
            $table->unsignedBigInteger('vendor_id');
            $table->unsignedBigInteger('store_id');
            $table->date('receipt_date');
            $table->string('invoice_number')->nullable();
            $table->decimal('invoice_amount', 12, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedInteger('received_by');
            $table->timestamps();
            
            $table->foreign('purchase_order_id')->references('id')->on('mess_purchase_orders')->onDelete('set null');
            $table->foreign('vendor_id')->references('id')->on('mess_vendors')->onDelete('cascade');
            $table->foreign('received_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('mess_inbound_transactions');
    }
};
