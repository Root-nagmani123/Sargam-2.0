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
        Schema::table('faculty_master', function (Blueprint $table) {
            $table->string('faculty_pa')->nullable()->after('faculty_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('faculty_master', function (Blueprint $table) {
            $table->dropColumn('faculty_pa');
        });
    }
};
