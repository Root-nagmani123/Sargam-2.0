<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('kitchen_issue_master') && !Schema::hasColumn('kitchen_issue_master', 'store_type')) {
            Schema::table('kitchen_issue_master', function (Blueprint $table) {
                $table->string('store_type', 20)->default('store')->after('store_id');
            });
        }

        if (Schema::hasTable('sv_date_range_reports') && !Schema::hasColumn('sv_date_range_reports', 'store_type')) {
            Schema::table('sv_date_range_reports', function (Blueprint $table) {
                $table->string('store_type', 20)->default('store')->after('store_id');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('kitchen_issue_master') && Schema::hasColumn('kitchen_issue_master', 'store_type')) {
            Schema::table('kitchen_issue_master', function (Blueprint $table) {
                $table->dropColumn('store_type');
            });
        }

        if (Schema::hasTable('sv_date_range_reports') && Schema::hasColumn('sv_date_range_reports', 'store_type')) {
            Schema::table('sv_date_range_reports', function (Blueprint $table) {
                $table->dropColumn('store_type');
            });
        }
    }
};
