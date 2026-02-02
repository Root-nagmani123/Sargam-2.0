<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Selling Voucher with Date Range - standalone module (not related to selling voucher / kitchen issue).
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sv_date_range_reports', function (Blueprint $table) {
            $table->id();
            $table->date('date_from')->comment('Report period start');
            $table->date('date_to')->comment('Report period end');
            $table->unsignedBigInteger('store_id')->comment('Store FK');
            $table->string('report_title')->nullable()->comment('Report title');
            $table->tinyInteger('status')->default(0)->comment('0=Draft, 1=Final');
            $table->decimal('total_amount', 12, 2)->default(0)->comment('Grand total');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index('store_id');
            $table->index(['date_from', 'date_to']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sv_date_range_reports');
    }
};
