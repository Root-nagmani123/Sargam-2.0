<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Backing store for the per-week footer of the weekly timetable PDF:
 *  - venue_line : the "VENUES: Full Group: VH, Group-A: VH, Group B: H" line
 *  - notes      : JSON array of numbered notes printed under the grid
 *  - mention_of_week / director / joint director / participants profile:
 *    fields the info-sheet editor (CalendarController::weeklyInfoMeta) already
 *    reads but which were never created — 2026_06_09_000002 is recorded as run
 *    in some environments while its columns are absent, so every statement here
 *    is guarded and this migration is safe to run alongside it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('course_week_notes')) {
            Schema::create('course_week_notes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('course_master_pk');
                $table->date('week_start')->comment('Monday of the week these notes apply to');
                $table->text('venue_line')->nullable()->comment('Printed under the grid, e.g. "Full Group: VH, Group-A: VH"');
                $table->json('notes')->nullable()->comment('Ordered list of note strings, rendered numbered');
                $table->text('mention_of_week')->nullable();
                $table->timestamps();

                $table->unique(['course_master_pk', 'week_start'], 'course_week_notes_course_week_unique');
                $table->index('week_start');
            });
        } else {
            Schema::table('course_week_notes', function (Blueprint $table) {
                if (!Schema::hasColumn('course_week_notes', 'venue_line')) {
                    $table->text('venue_line')->nullable();
                }
                if (!Schema::hasColumn('course_week_notes', 'notes')) {
                    $table->json('notes')->nullable();
                }
            });
        }

        Schema::table('course_coordinator_master', function (Blueprint $table) {
            if (!Schema::hasColumn('course_coordinator_master', 'director_name')) {
                $table->string('director_name', 255)->nullable();
            }
            if (!Schema::hasColumn('course_coordinator_master', 'joint_director_name')) {
                $table->string('joint_director_name', 255)->nullable();
            }
        });

        Schema::table('course_master', function (Blueprint $table) {
            if (!Schema::hasColumn('course_master', 'participants_profile')) {
                $table->text('participants_profile')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_week_notes');

        Schema::table('course_coordinator_master', function (Blueprint $table) {
            foreach (['director_name', 'joint_director_name'] as $column) {
                if (Schema::hasColumn('course_coordinator_master', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('course_master', function (Blueprint $table) {
            if (Schema::hasColumn('course_master', 'participants_profile')) {
                $table->dropColumn('participants_profile');
            }
        });
    }
};
