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
        Schema::create('estate_meter_reading', function (Blueprint $table) {
            $table->id('pk');
            $table->unsignedBigInteger('estate_possession_pk');
            $table->string('meter_number', 100);
            $table->integer('previous_reading')->default(0);
            $table->integer('current_reading');
            $table->integer('units_consumed')->default(0);
            $table->date('reading_date');
            $table->integer('month');
            $table->integer('year');
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
            
            $table->index(['month', 'year']);
            $table->index('reading_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estate_meter_reading');
    }
};
