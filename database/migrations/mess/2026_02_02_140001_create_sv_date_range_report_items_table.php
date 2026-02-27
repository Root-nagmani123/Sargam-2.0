<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Items for Selling Voucher with Date Range reports (standalone).
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sv_date_range_report_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sv_date_range_report_id');
            $table->unsignedBigInteger('item_subcategory_id')->nullable()->comment('Item subcategory FK');
            $table->string('item_name')->nullable()->comment('Item name (denormalized)');
            $table->string('unit', 20)->nullable();
            $table->decimal('quantity', 10, 2)->default(0);
            $table->decimal('rate', 10, 2)->default(0);
            $table->decimal('amount', 10, 2)->default(0);
            $table->timestamps();

            $table->foreign('sv_date_range_report_id')
                  ->references('id')
                  ->on('sv_date_range_reports')
                  ->onDelete('cascade');
            $table->index('sv_date_range_report_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sv_date_range_report_items');
    }
};
