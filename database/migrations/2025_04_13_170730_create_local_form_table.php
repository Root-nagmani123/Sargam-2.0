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
        Schema::create('local_form', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('shortname', 100);
            $table->text('description');
            $table->date('course_sdate');
            $table->date('course_edate');
            $table->boolean('visible')->default(false);
            $table->boolean('fc_registration')->default(false);
            $table->boolean('createcohort')->default(false);
            $table->integer('sortorder')->default(0);
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
        Schema::dropIfExists('local_form');
    }
};
