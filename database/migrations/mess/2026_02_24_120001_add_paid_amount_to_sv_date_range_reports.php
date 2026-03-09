<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add paid_amount to cache total paid for process mess bills (partial/full payment).
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('sv_date_range_reports', 'paid_amount')) {
            Schema::table('sv_date_range_reports', function (Blueprint $table) {
                $table->decimal('paid_amount', 12, 2)->default(0)->after('total_amount')->comment('Total paid amount (sum of payment details)');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('sv_date_range_reports', 'paid_amount')) {
            Schema::table('sv_date_range_reports', function (Blueprint $table) {
                $table->dropColumn('paid_amount');
            });
        }
    }
};
