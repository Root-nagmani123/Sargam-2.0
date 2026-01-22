<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('mess_stores', function (Blueprint $table) {
            $table->id();
            $table->string('store_name');
            $table->string('store_code')->unique();
            $table->string('location')->nullable();
            $table->string('incharge_name')->nullable();
            $table->string('incharge_contact')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('mess_stores');
    }
};
