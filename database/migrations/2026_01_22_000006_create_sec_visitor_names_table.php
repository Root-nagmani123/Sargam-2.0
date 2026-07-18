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
        Schema::create('sec_visitor_names', function (Blueprint $table) {
            $table->id('pk');
            // Match sec_visitor_card_generated.pk which is a signed INT auto_increment,
            // otherwise MySQL rejects the FK (error 3780: incompatible columns).
            $table->integer('sec_visitor_card_generated_pk');
            $table->string('visitor_name', 255);
            $table->timestamp('created_date')->nullable();
            
            $table->foreign('sec_visitor_card_generated_pk')->references('pk')->on('sec_visitor_card_generated')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sec_visitor_names');
    }
};
