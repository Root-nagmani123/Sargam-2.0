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
            if (! Schema::hasColumn('course_master', 'created_by')) {
                // Stores the id of the user who created the course (Auth::id()).
                // Nullable so pre-existing rows and non-create paths remain valid.
                $table->unsignedBigInteger('created_by')->nullable()->after('user_role_master_pk');
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
            if (Schema::hasColumn('course_master', 'created_by')) {
                $table->dropColumn('created_by');
            }
        });
    }
};
