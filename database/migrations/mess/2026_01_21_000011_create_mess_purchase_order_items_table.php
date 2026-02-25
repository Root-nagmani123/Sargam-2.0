<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('mess_purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_order_id');
            $table->unsignedBigInteger('inventory_id');
            $table->decimal('quantity', 10, 2);
            $table->string('unit')->nullable();
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 12, 2);
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->foreign('purchase_order_id')->references('id')->on('mess_purchase_orders')->onDelete('cascade');
            $table->foreign('inventory_id')->references('id')->on('mess_inventories')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('mess_purchase_order_items');
    }
};
