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
        Schema::create('kitchen_issue_items', function (Blueprint $table) {
            $table->id('pk');
            $table->unsignedBigInteger('kitchen_issue_master_pk')->comment('Kitchen Issue Master FK');
            $table->string('item_name')->comment('Item Name');
            $table->decimal('quantity', 10, 2)->default(0)->comment('Item Quantity');
            $table->decimal('rate', 10, 2)->default(0)->comment('Item Rate');
            $table->decimal('amount', 10, 2)->default(0)->comment('Total Amount');
            $table->string('unit')->nullable()->comment('Unit of Measurement');
            $table->timestamps();

            // Foreign key
            $table->foreign('kitchen_issue_master_pk')
                  ->references('pk')
                  ->on('kitchen_issue_master')
                  ->onDelete('cascade');

            // Indexes
            $table->index('kitchen_issue_master_pk');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('kitchen_issue_items');
    }
};
