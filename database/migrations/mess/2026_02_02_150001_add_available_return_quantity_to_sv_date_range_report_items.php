<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Same item columns as Selling Voucher: available_quantity, return_quantity.
     */
    public function up()
    {
        Schema::table('sv_date_range_report_items', function (Blueprint $table) {
            $table->decimal('available_quantity', 10, 2)->default(0)->after('unit')->comment('Available Qty');
            $table->decimal('return_quantity', 10, 2)->default(0)->after('quantity')->comment('Return Qty');
        });
    }

    public function down()
    {
        Schema::table('sv_date_range_report_items', function (Blueprint $table) {
            $table->dropColumn(['available_quantity', 'return_quantity']);
        });
    }
};
