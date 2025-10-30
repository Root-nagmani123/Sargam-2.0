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
        Schema::table('course_coordinator_master', function (Blueprint $table) {
            $table->string('assistant_coordinator_role', 255)->nullable()->after('Assistant_Coordinator_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('course_coordinator_master', function (Blueprint $table) {
            $table->dropColumn('assistant_coordinator_role');
        });
    }
};
