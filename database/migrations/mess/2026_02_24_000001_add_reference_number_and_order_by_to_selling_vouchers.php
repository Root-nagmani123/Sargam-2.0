<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add reference_number and order_by to Selling Voucher (kitchen_issue_master) and Selling Voucher with Date Range (sv_date_range_reports).
     */
    public function up()
    {
        Schema::table('kitchen_issue_master', function (Blueprint $table) {
            if (!Schema::hasColumn('kitchen_issue_master', 'reference_number')) {
                $table->string('reference_number', 100)->nullable()->after('remarks');
            }
            if (!Schema::hasColumn('kitchen_issue_master', 'order_by')) {
                $table->string('order_by', 100)->nullable()->after('reference_number');
            }
        });

        Schema::table('sv_date_range_reports', function (Blueprint $table) {
            if (!Schema::hasColumn('sv_date_range_reports', 'reference_number')) {
                $table->string('reference_number', 100)->nullable()->after('remarks');
            }
            if (!Schema::hasColumn('sv_date_range_reports', 'order_by')) {
                $table->string('order_by', 100)->nullable()->after('reference_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('kitchen_issue_master', function (Blueprint $table) {
            if (Schema::hasColumn('kitchen_issue_master', 'reference_number')) {
                $table->dropColumn('reference_number');
            }
            if (Schema::hasColumn('kitchen_issue_master', 'order_by')) {
                $table->dropColumn('order_by');
            }
        });

        Schema::table('sv_date_range_reports', function (Blueprint $table) {
            if (Schema::hasColumn('sv_date_range_reports', 'reference_number')) {
                $table->dropColumn('reference_number');
            }
            if (Schema::hasColumn('sv_date_range_reports', 'order_by')) {
                $table->dropColumn('order_by');
            }
        });
    }
};
