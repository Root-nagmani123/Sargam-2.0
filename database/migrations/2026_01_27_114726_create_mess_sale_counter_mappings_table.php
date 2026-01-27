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
        Schema::create('mess_sale_counter_mappings', function (Blueprint $table) {
            $table->id();            $table->unsignedBigInteger('sale_counter_id');
            $table->unsignedBigInteger('inventory_id');
            $table->integer('available_quantity')->default(0);
            $table->boolean('is_active')->default(true);            $table->timestamps();
            
            $table->index('sale_counter_id');
            $table->index('inventory_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mess_sale_counter_mappings');
    }
};
