<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Curated initials for the printed weekly timetable.
 *
 * The issued sheet prints a course-team coordinator as a short code beside the
 * session taker - "(MK)", "(KP)". Those codes are decided by the course team,
 * not derived: the sheet has MK for D Mahesh Kumar (not DMK) and KP for
 * Kranthi Kumar Pati (not KKP). Without a column to hold them the timetable
 * PDF can only fall back to initials built from full_name, which gets those
 * two wrong.
 *
 * Guarded both ways because the live schema of the older masters has drifted
 * from the migration history - see CalendarController's timetable exports.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('faculty_master')) {
            return;
        }
        if (Schema::hasColumn('faculty_master', 'abbreviation')) {
            return;
        }

        Schema::table('faculty_master', function (Blueprint $table) {
            $table->string('abbreviation', 12)->nullable()->after('full_name');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('faculty_master')) {
            return;
        }
        if (!Schema::hasColumn('faculty_master', 'abbreviation')) {
            return;
        }

        Schema::table('faculty_master', function (Blueprint $table) {
            $table->dropColumn('abbreviation');
        });
    }
};
