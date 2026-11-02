<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two more pieces of the issued weekly sheet that no master holds:
 *
 *  - course_master.sheet_title       : the programme title as the sheet prints it,
 *                                      e.g. "IAS Professional Course, Phase - II (2024 Batch)",
 *                                      without renaming the course everywhere else.
 *  - course_week_notes.venue_legend  : the "Venues Abbreviation" box when the sheet
 *                                      prints the academy's standard list rather than
 *                                      only the venues the week uses.
 *
 * Guarded, as the rest of the timetable schema is.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('course_master') && !Schema::hasColumn('course_master', 'sheet_title')) {
            Schema::table('course_master', function (Blueprint $table) {
                $table->string('sheet_title', 255)->nullable()->comment('Programme title printed on the weekly timetable');
            });
        }

        if (Schema::hasTable('course_week_notes') && !Schema::hasColumn('course_week_notes', 'venue_legend')) {
            Schema::table('course_week_notes', function (Blueprint $table) {
                // [{"abbreviation": "VH", "name": "Vivekanand Hall (Aadharshila Building)"}]
                $table->json('venue_legend')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('course_master', 'sheet_title')) {
            Schema::table('course_master', function (Blueprint $table) {
                $table->dropColumn('sheet_title');
            });
        }
        if (Schema::hasTable('course_week_notes') && Schema::hasColumn('course_week_notes', 'venue_legend')) {
            Schema::table('course_week_notes', function (Blueprint $table) {
                $table->dropColumn('venue_legend');
            });
        }
    }
};
