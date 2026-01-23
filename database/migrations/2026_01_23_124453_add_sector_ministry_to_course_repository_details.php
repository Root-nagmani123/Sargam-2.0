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
            $table->unsignedBigInteger('sector_master_pk')->nullable()->after('author_name');
            $table->unsignedBigInteger('ministry_master_pk')->nullable()->after('sector_master_pk');
            $table->foreign('sector_master_pk')->references('pk')->on('sector_master')->cascadeOnDelete();
            $table->foreign('ministry_master_pk')->references('pk')->on('ministry_master')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('course_repository_details', function (Blueprint $table) {
            $table->dropForeign(['sector_master_pk']);
            $table->dropForeign(['ministry_master_pk']);
            $table->dropColumn(['sector_master_pk', 'ministry_master_pk']);
        });
    }
};
