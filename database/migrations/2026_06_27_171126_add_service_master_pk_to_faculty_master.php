<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('faculty_master', function (Blueprint $table) {
            $table->unsignedBigInteger('service_master_pk')->nullable()->after('alternate_email_id');
        });
    }

    public function down()
    {
        Schema::table('faculty_master', function (Blueprint $table) {
            $table->dropColumn('service_master_pk');
        });
    }
};
