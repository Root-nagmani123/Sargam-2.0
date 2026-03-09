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
        Schema::create('mess_monthly_bills', function (Blueprint $table) {
            $table->id();            $table->unsignedBigInteger('user_id');
            $table->string('bill_number')->unique();
            $table->integer('month');
            $table->integer('year');
            $table->decimal('total_amount', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('balance', 10, 2);
            $table->string('status')->default('pending'); // pending, paid, overdue
            $table->date('due_date')->nullable();
            $table->date('paid_date')->nullable();            $table->timestamps();
            
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
        Schema::dropIfExists('mess_monthly_bills');
    }
};
