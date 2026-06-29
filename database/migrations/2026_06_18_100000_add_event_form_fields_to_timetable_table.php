<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{ 
    /**
     * Fields backing the redesigned full-page "Add Event" form:
     *  - sector_pk        : optional sector_master reference for the session
     *  - is_break         : marks a session as a tea/lunch break
     *  - faculty_details  : JSON array of per-faculty rows
     *                       [{ "faculty_pk": int, "faculty_type": int, "role": string, "feedback": "remark|rating|none" }]
     *
     * All columns are nullable so existing timetable rows and the modal-based
     * add/edit flow remain unaffected.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('timetable', function (Blueprint $table) {
            if (!Schema::hasColumn('timetable', 'sector_pk')) {
                $table->unsignedBigInteger('sector_pk')->nullable()->comment('sector_master.pk for the session');
            }
            if (!Schema::hasColumn('timetable', 'is_break')) {
                $table->tinyInteger('is_break')->nullable()->default(0)->comment('1 = tea/lunch break session');
            }
            if (!Schema::hasColumn('timetable', 'faculty_details')) {
                $table->text('faculty_details')->nullable()->comment('JSON of per-faculty rows: faculty_pk, faculty_type, role, feedback');
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
        Schema::table('timetable', function (Blueprint $table) {
            foreach (['sector_pk', 'is_break', 'faculty_details'] as $column) {
                if (Schema::hasColumn('timetable', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
