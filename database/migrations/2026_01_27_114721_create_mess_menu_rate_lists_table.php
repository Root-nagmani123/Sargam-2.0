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
        Schema::create('mess_menu_rate_lists', function (Blueprint $table) {
            $table->id();            $table->string('menu_item_name');
            $table->unsignedBigInteger('inventory_id')->nullable();
            $table->decimal('rate', 10, 2);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);            $table->timestamps();
            
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
        Schema::dropIfExists('mess_menu_rate_lists');
    }
};
