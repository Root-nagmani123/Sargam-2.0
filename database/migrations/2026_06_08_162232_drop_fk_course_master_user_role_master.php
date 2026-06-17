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
            // Drop FK so user_role_master_pk can now store roles.id (Spatie roles table)
            $table->dropForeign('fk_course_master_user_role_master');
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
            $table->foreign('user_role_master_pk', 'fk_course_master_user_role_master')
                ->references('pk')->on('user_role_master')
                ->nullOnDelete();
        });
    }
};
