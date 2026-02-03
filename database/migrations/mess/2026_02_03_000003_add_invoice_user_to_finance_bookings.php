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
        Schema::table('mess_finance_bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('mess_finance_bookings', 'invoice_id')) {
                $table->unsignedBigInteger('invoice_id')->nullable()->after('booking_number');
                $table->index('invoice_id');
            }
            
            if (!Schema::hasColumn('mess_finance_bookings', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('invoice_id');
                $table->index('user_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mess_finance_bookings', function (Blueprint $table) {
            $table->dropColumn(['invoice_id', 'user_id']);
        });
    }
};
