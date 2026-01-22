<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('estate_billing', function (Blueprint $table) {
            $table->id('pk');
            $table->unsignedBigInteger('estate_possession_pk');
            $table->unsignedBigInteger('estate_meter_reading_pk')->nullable();
            $table->string('bill_number', 100)->unique();
            $table->date('bill_date');
            $table->integer('month');
            $table->integer('year');
            $table->decimal('rent_amount', 15, 2)->default(0);
            $table->decimal('electricity_amount', 15, 2)->default(0);
            $table->decimal('water_amount', 15, 2)->default(0);
            $table->decimal('other_charges', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->enum('payment_status', ['pending', 'paid', 'partial'])->default('pending');
            $table->date('payment_date')->nullable();
            $table->date('due_date')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_date')->nullable();
            $table->unsignedBigInteger('modify_by')->nullable();
            $table->timestamp('modify_date')->nullable();
            $table->timestamps();
            
            $table->foreign('estate_possession_pk')
                  ->references('pk')
                  ->on('estate_possession')
                  ->onDelete('cascade');
                  
            $table->foreign('estate_meter_reading_pk')
                  ->references('pk')
                  ->on('estate_meter_reading')
                  ->onDelete('set null');
            
            $table->index('payment_status');
            $table->index(['month', 'year']);
            $table->index('bill_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estate_billing');
    }
};
