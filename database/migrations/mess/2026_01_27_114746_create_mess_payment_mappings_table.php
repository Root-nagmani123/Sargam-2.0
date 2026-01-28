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
        Schema::create('mess_payment_mappings', function (Blueprint $table) {
            $table->id();            $table->string('mapping_type'); // section, individual, event
            $table->string('reference_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('event_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->date('payment_date');
            $table->string('payment_mode')->nullable(); // cash, card, online
            $table->string('transaction_reference')->nullable();            $table->timestamps();
            
            $table->index('user_id');
            $table->index('event_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mess_payment_mappings');
    }
};
