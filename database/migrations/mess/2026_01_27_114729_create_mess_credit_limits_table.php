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
        Schema::create('mess_credit_limits', function (Blueprint $table) {
            $table->id();            $table->unsignedBigInteger('user_id');
            $table->string('client_type'); // student, employee, guest
            $table->decimal('credit_limit', 10, 2);
            $table->decimal('current_balance', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);            $table->timestamps();
            
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mess_credit_limits');
    }
};
