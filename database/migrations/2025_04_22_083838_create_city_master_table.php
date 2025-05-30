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
        Schema::create('city_master', function (Blueprint $table) {
            $table->bigIncrements('pk');
            $table->unsignedBigInteger('state_master_pk');
            $table->unsignedBigInteger('district_master_pk');
            $table->string('city_name', 100);
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('city_master');
    }
};
