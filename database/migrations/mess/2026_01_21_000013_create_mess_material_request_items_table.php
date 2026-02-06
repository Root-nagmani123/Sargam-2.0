<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('mess_material_request_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('material_request_id');
            $table->unsignedBigInteger('inventory_id');
            $table->decimal('requested_quantity', 10, 2);
            $table->decimal('approved_quantity', 10, 2)->nullable();
            $table->string('unit')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            
            $table->foreign('material_request_id')->references('id')->on('mess_material_requests')->onDelete('cascade');
            $table->foreign('inventory_id')->references('id')->on('mess_inventories')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('mess_material_request_items');
    }
};
