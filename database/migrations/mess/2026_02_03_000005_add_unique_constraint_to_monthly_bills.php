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
            // Add unique constraint on user_id, month, year combination
            // This prevents duplicate bills for the same user in the same month
            $table->unique(['user_id', 'month', 'year'], 'unique_user_month_year');
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
            $table->dropUnique('unique_user_month_year');
        });
    }
};
