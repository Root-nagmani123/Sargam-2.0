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
        Schema::table('mess_monthly_bills', function (Blueprint $table) {
            if (!Schema::hasColumn('mess_monthly_bills', 'month_year')) {
                $table->date('month_year')->nullable()->after('year');
            }
            if (!Schema::hasColumn('mess_monthly_bills', 'remarks')) {
                $table->text('remarks')->nullable()->after('paid_date');
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
        Schema::table('mess_monthly_bills', function (Blueprint $table) {
            if (Schema::hasColumn('mess_monthly_bills', 'month_year')) {
                $table->dropColumn('month_year');
            }
            if (Schema::hasColumn('mess_monthly_bills', 'remarks')) {
                $table->dropColumn('remarks');
            }
        });
    }
};
