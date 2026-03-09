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
        Schema::create('mess_sub_stores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_store_id');
            $table->string('sub_store_name');
            $table->string('status')->default('active');
            $table->timestamps();
            
            $table->foreign('parent_store_id')->references('id')->on('mess_stores')->onDelete('cascade');
            $table->index('parent_store_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mess_sub_stores');
    }
};
