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
        if (Schema::hasTable('course_coordinator_master')) {
            Schema::table('course_coordinator_master', function (Blueprint $table) {
                if (!Schema::hasColumn('course_coordinator_master', 'assistant_coordinator_role')) {
                    $table->string('assistant_coordinator_role', 255)->nullable()->after('Assistant_Coordinator_name');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('course_coordinator_master')) {
            Schema::table('course_coordinator_master', function (Blueprint $table) {
                if (Schema::hasColumn('course_coordinator_master', 'assistant_coordinator_role')) {
                    $table->dropColumn('assistant_coordinator_role');
                }
            });
        }
    }
};
