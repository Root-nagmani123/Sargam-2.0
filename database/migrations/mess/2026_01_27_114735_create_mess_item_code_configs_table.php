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
        Schema::create('mess_item_code_configs', function (Blueprint $table) {
            $table->id();            $table->string('prefix', 10);
            $table->integer('current_number')->default(0);
            $table->integer('padding')->default(4);
            $table->string('sample_format')->nullable();            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mess_item_code_configs');
    }
};
