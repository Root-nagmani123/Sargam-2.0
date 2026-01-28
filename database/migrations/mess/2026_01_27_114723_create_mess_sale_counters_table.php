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
        Schema::create('mess_sale_counters', function (Blueprint $table) {
            $table->id();            $table->string('counter_name');
            $table->string('counter_code')->unique();
            $table->unsignedBigInteger('store_id');
            $table->string('location')->nullable();
            $table->boolean('is_active')->default(true);            $table->timestamps();
            
            $table->index('store_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mess_sale_counters');
    }
};
