<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        if (Schema::hasTable('sv_date_range_reports') && !Schema::hasColumn('sv_date_range_reports', 'client_id')) {
            Schema::table('sv_date_range_reports', function (Blueprint $table) {
                $table->unsignedBigInteger('client_id')->nullable()->after('client_type_pk')
                    ->comment('Employee/Student PK when client_type_slug is employee or ot');
                $table->index('client_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        if (Schema::hasTable('sv_date_range_reports') && Schema::hasColumn('sv_date_range_reports', 'client_id')) {
            Schema::table('sv_date_range_reports', function (Blueprint $table) {
                $table->dropIndex(['client_id']);
                $table->dropColumn('client_id');
            });
        }
    }
};
