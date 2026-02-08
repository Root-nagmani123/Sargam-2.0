<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add return_date to kitchen_issue_items and sv_date_range_report_items for Return modal.
     */
    public function up()
    {
        if (Schema::hasTable('kitchen_issue_items') && !Schema::hasColumn('kitchen_issue_items', 'return_date')) {
            Schema::table('kitchen_issue_items', function (Blueprint $table) {
                $table->date('return_date')->nullable()->after('return_quantity')->comment('Date of return');
            });
        }

        if (Schema::hasTable('sv_date_range_report_items') && !Schema::hasColumn('sv_date_range_report_items', 'return_date')) {
            Schema::table('sv_date_range_report_items', function (Blueprint $table) {
                $table->date('return_date')->nullable()->after('return_quantity')->comment('Date of return');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        if (Schema::hasTable('kitchen_issue_items') && Schema::hasColumn('kitchen_issue_items', 'return_date')) {
            Schema::table('kitchen_issue_items', function (Blueprint $table) {
                $table->dropColumn('return_date');
            });
        }
        if (Schema::hasTable('sv_date_range_report_items') && Schema::hasColumn('sv_date_range_report_items', 'return_date')) {
            Schema::table('sv_date_range_report_items', function (Blueprint $table) {
                $table->dropColumn('return_date');
            });
        }
    }
};
