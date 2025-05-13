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
        Schema::create('form_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('formid')->nullable();
            $table->unsignedBigInteger('section_id')->nullable();
            $table->string('formname')->nullable();
            $table->string('formtype')->nullable();
            $table->string('formlabel')->nullable();
            $table->text('fieldoption')->nullable();
            $table->boolean('required')->default(false);
            $table->string('layout')->nullable();
            $table->integer('table_index')->nullable();
            $table->string('format')->nullable(); // 'table' or 'field'
            $table->integer('row_index')->nullable();
            $table->integer('col_index')->nullable();
            $table->string('header')->nullable();
            $table->string('field_type')->nullable();
            $table->string('field_title')->nullable();
            $table->string('field_url')->nullable();
            $table->text('field_options')->nullable();
            $table->text('field_checkbox_options')->nullable();
            $table->text('field_radio_options')->nullable();
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
        Schema::dropIfExists('form_data');
    }
};
