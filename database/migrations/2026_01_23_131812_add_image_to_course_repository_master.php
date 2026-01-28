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
        Schema::table('course_repository_master', function (Blueprint $table) {
            $table->string('category_image')->nullable()->after('course_repository_details')->comment('Category image path');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('course_repository_master', function (Blueprint $table) {
            $table->dropColumn('category_image');
        });
    }
};
