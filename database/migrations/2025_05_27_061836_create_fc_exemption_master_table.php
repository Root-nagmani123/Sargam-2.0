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
        Schema::create('fc_exemption_master', function (Blueprint $table) {
            $table->bigIncrements('Pk');
            $table->string('Exemption_name', 500);
            $table->string('Exemption_short_name', 100);
            $table->unsignedBigInteger('Created_by')->nullable();
            $table->dateTime('Created_date');
            $table->unsignedBigInteger('Modified_by')->nullable();
            $table->timestamp('Modified_date')->nullable()->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fc_exemption_master');
    }
};
