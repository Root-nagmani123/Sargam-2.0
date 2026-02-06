<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('sv_date_range_report_items', function (Blueprint $table) {
            $table->date('issue_date')->nullable()->after('return_quantity')->comment('Issue Date per item');
        });
    }

    public function down()
    {
        Schema::table('sv_date_range_report_items', function (Blueprint $table) {
            $table->dropColumn('issue_date');
        });
    }
};
