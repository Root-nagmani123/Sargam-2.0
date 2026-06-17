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
        Schema::table('course_repository_details', function (Blueprint $table) {
            if (!Schema::hasColumn('course_repository_details', 'course_master_pk')) {
                $table->unsignedBigInteger('course_master_pk')->nullable()->after('course_repository_type');
            }
        });
    }

    public function down()
    {
        Schema::table('course_repository_details', function (Blueprint $table) {
            if (Schema::hasColumn('course_repository_details', 'course_master_pk')) {
                $table->dropColumn('course_master_pk');
            }
        });
    }
};
