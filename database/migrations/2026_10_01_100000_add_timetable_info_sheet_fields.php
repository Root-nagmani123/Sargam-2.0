<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-week content of the printed weekly timetable that no master models:
 *
 *  - venue_line / notes      : the "VENUES: ..." line and the numbered notes
 *                              printed under the grid.
 *  - counsellor_meta         : {"<faculty_pk>": {"label": "JD(SW)", "venue": "SR- I", "cadres": "..."}}
 *  - guest_moderators        : {"<faculty_pk>": "T Bhuvaneshram, B02"}
 *  - language_venues         : [{"language": "Hindi", "venue": "SR-A & B (Karmashila)"}]
 *  - outdoor_activities      : the "Outdoor and Other Activities" block.
 *  - signatory_*             : the signature block on the P.T.O. page.
 *
 * course_week_notes is created by 2026_06_09_000002, which is recorded as run in
 * some environments while its table is absent, so the table is created here too
 * when missing and every statement is guarded.
 */
return new class extends Migration
{
    private const COLUMNS = [
        'venue_line', 'notes', 'counsellor_meta', 'guest_moderators', 'language_venues',
        'outdoor_activities', 'signatory_name', 'signatory_designation', 'signatory_date',
    ];

    public function up(): void
    {
        if (!Schema::hasTable('course_week_notes')) {
            Schema::create('course_week_notes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('course_master_pk');
                $table->date('week_start')->comment('Monday of the week these notes apply to');
                $table->text('mention_of_week')->nullable();
                $table->timestamps();

                $table->unique(['course_master_pk', 'week_start'], 'course_week_notes_course_week_unique');
                $table->index('week_start');
            });
        }

        Schema::table('course_week_notes', function (Blueprint $table) {
            if (!Schema::hasColumn('course_week_notes', 'venue_line')) {
                $table->text('venue_line')->nullable();
            }
            if (!Schema::hasColumn('course_week_notes', 'notes')) {
                $table->json('notes')->nullable();
            }
            if (!Schema::hasColumn('course_week_notes', 'counsellor_meta')) {
                $table->json('counsellor_meta')->nullable();
            }
            if (!Schema::hasColumn('course_week_notes', 'guest_moderators')) {
                $table->json('guest_moderators')->nullable();
            }
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
        if (!Schema::hasTable('course_week_notes')) {
            return;
        }

        Schema::table('course_week_notes', function (Blueprint $table) {
            foreach (self::COLUMNS as $column) {
                if (Schema::hasColumn('course_week_notes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
