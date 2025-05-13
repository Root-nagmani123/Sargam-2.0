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
        Schema::create('form_submissions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('formid')->nullable();
            $table->unsignedBigInteger('uid')->nullable();
            $table->string('fieldname', 255)->nullable();
            $table->longText('fieldvalue')->nullable();
            $table->integer('visible')->default(1);
            $table->unsignedBigInteger('confirm_status')->default(1)->nullable();
            $table->unsignedBigInteger('timecreated')->nullable();
            $table->integer('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('form_submissions');
    }
};
