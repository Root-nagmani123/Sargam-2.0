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
        Schema::create('mess_finance_bookings', function (Blueprint $table) {
            $table->id();            $table->string('booking_number')->unique();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('inbound_transaction_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->date('booking_date');
            $table->string('account_head')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();            $table->timestamps();
            
            $table->index('invoice_id');
            $table->index('user_id');
            $table->index('inbound_transaction_id');
            $table->index('approved_by');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mess_finance_bookings');
    }
};
