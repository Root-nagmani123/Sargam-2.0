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
        Schema::create('mess_client_types', function (Blueprint $table) {
            $table->id();            $table->string('type_name')->unique();
            $table->string('type_code')->unique();
            $table->text('description')->nullable();
            $table->decimal('default_credit_limit', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mess_client_types');
    }
};
