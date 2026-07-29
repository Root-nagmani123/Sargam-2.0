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
        Schema::table('course_master', function (Blueprint $table) {
            if (! Schema::hasColumn('course_master', 'pt_start_time')) {
                $table->time('pt_start_time')->nullable()->after('end_date');
            }
            if (! Schema::hasColumn('course_master', 'pt_end_time')) {
                $table->time('pt_end_time')->nullable()->after('pt_start_time');
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
        Schema::table('course_master', function (Blueprint $table) {
            if (Schema::hasColumn('course_master', 'pt_end_time')) {
                $table->dropColumn('pt_end_time');
            }
            if (Schema::hasColumn('course_master', 'pt_start_time')) {
                $table->dropColumn('pt_start_time');
            }
        });
    }
};
