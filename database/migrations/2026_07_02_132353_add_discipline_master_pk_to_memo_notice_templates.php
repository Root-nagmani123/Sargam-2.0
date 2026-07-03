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
        Schema::table('memo_notice_templates', function (Blueprint $table) {
            if (! Schema::hasColumn('memo_notice_templates', 'discipline_master_pk')) {
                // Null = course-wide discipline template (fallback when no discipline-specific one exists).
                $table->unsignedBigInteger('discipline_master_pk')->nullable()->after('course_master_pk');
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
        Schema::table('memo_notice_templates', function (Blueprint $table) {
            if (Schema::hasColumn('memo_notice_templates', 'discipline_master_pk')) {
                $table->dropColumn('discipline_master_pk');
            }
        });
    }
};
