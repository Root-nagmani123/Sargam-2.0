<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fields the circulated weekly timetable sheet needs but the schema never had:
 *
 *  - faculty_master.abbreviation — the "MK" / "GSM" / "ARK" short codes printed
 *    in the grid cells and listed in the Faculty Abbreviation legend. Curated,
 *    not derivable: the legend has "MK : D Mahesh Kumar" (not DMK) and
 *    "ARK : Arvind Kumar" (not AK — AK is taken by Aakanksha Kulshrestha).
 *  - timetable.remarks — per-session notes printed under the faculty line,
 *    e.g. "(Half group)", "(Separate schedule to be issued)".
 *  - course_week_notes.* — the page-2 info sheet content that has no source
 *    table, held per course + week and edited from the Weekly Info editor.
 *
 * Every statement is guarded; see the schema-drift note on this repo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faculty_master', function (Blueprint $table) {
            if (!Schema::hasColumn('faculty_master', 'abbreviation')) {
                $table->string('abbreviation', 12)->nullable()
                    ->comment('Short code printed on the timetable, e.g. MK, GSM, ARK');
            }
        });

        Schema::table('timetable', function (Blueprint $table) {
            if (!Schema::hasColumn('timetable', 'remarks')) {
                $table->text('remarks')->nullable()
                    ->comment('Printed under the faculty line, e.g. "(Half group)"');
            }
        });

        Schema::table('course_week_notes', function (Blueprint $table) {
            // {"<faculty_pk>": {"label": "JD(SW)", "venue": "Session Design Lab"}}
            if (!Schema::hasColumn('course_week_notes', 'counsellor_meta')) {
                $table->json('counsellor_meta')->nullable();
            }
            // {"<guest faculty_pk>": "Ms. Nausheen, B01"}
            if (!Schema::hasColumn('course_week_notes', 'guest_moderators')) {
                $table->json('guest_moderators')->nullable();
            }
            // [{"language": "Hindi", "venue": "SR-A & B (Karmashila)"}]
            if (!Schema::hasColumn('course_week_notes', 'language_venues')) {
                $table->json('language_venues')->nullable();
            }
            if (!Schema::hasColumn('course_week_notes', 'outdoor_activities')) {
                $table->text('outdoor_activities')->nullable();
            }
            if (!Schema::hasColumn('course_week_notes', 'signatory_name')) {
                $table->string('signatory_name', 255)->nullable();
            }
            if (!Schema::hasColumn('course_week_notes', 'signatory_designation')) {
                $table->string('signatory_designation', 255)->nullable();
            }
            if (!Schema::hasColumn('course_week_notes', 'signatory_date')) {
                $table->date('signatory_date')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('faculty_master', function (Blueprint $table) {
            if (Schema::hasColumn('faculty_master', 'abbreviation')) {
                $table->dropColumn('abbreviation');
            }
        });

        Schema::table('timetable', function (Blueprint $table) {
            if (Schema::hasColumn('timetable', 'remarks')) {
                $table->dropColumn('remarks');
            }
        });

        Schema::table('course_week_notes', function (Blueprint $table) {
            foreach ([
                'counsellor_meta', 'guest_moderators', 'language_venues',
                'outdoor_activities', 'signatory_name', 'signatory_designation', 'signatory_date',
            ] as $column) {
                if (Schema::hasColumn('course_week_notes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
