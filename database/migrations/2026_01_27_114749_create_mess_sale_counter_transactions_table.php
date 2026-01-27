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
        Schema::create('mess_sale_counter_transactions', function (Blueprint $table) {
            $table->id();            $table->string('transaction_number')->unique();
            $table->unsignedBigInteger('sale_counter_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('inventory_id');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_amount', 10, 2);
            $table->string('payment_mode'); // cash, card, credit
            $table->timestamp('transaction_date');            $table->timestamps();
            
            $table->index('sale_counter_id');
            $table->index('inventory_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mess_sale_counter_transactions');
    }
};
