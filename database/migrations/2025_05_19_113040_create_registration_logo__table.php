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
         Schema::create('registration_logo', function (Blueprint $table) {
        $table->id();
        $table->string('logo1')->nullable();
        $table->string('logo2')->nullable();
        $table->string('logo3')->nullable();
        $table->string('logo4')->nullable();
        $table->string('heading');
        $table->string('sub_heading');
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
        Schema::dropIfExists('registration_logo_');
    }
};
