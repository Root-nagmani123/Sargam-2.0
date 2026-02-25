<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('mess_inbound_transaction_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inbound_transaction_id');
            $table->unsignedBigInteger('inventory_id');
            $table->decimal('quantity', 10, 2);
            $table->string('unit')->nullable();
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->decimal('total_price', 12, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            
            $table->foreign('inbound_transaction_id')->references('id')->on('mess_inbound_transactions')->onDelete('cascade');
            $table->foreign('inventory_id')->references('id')->on('mess_inventories')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('mess_inbound_transaction_items');
    }
};
