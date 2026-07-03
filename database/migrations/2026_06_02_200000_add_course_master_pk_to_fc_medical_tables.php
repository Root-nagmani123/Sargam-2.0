<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add course_master_pk (INT) to fc_ot_details and fc_pre_history so that the
 * medical module stores a typed foreign key instead of a plain course-name string.
 *
 * The old `course` string column is kept for backward compat (fc_otactivity_details,
 * fc_path_report, fc_final_findings still join on the string).
 * All new writes will populate BOTH columns; the integer is the authoritative one
 * for the medical-list filter and pre-history existence badge.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fc_ot_details') && ! Schema::hasColumn('fc_ot_details', 'course_master_pk')) {
            Schema::table('fc_ot_details', function (Blueprint $table) {
                $table->unsignedInteger('course_master_pk')->nullable()->after('course');
            });
        }

        if (Schema::hasTable('fc_pre_history') && ! Schema::hasColumn('fc_pre_history', 'course_master_pk')) {
            Schema::table('fc_pre_history', function (Blueprint $table) {
                $table->unsignedInteger('course_master_pk')->nullable()->after('course');
            });
        }

        // Backfill: map existing course name strings to course_master.pk
        if (Schema::hasTable('course_master')) {
            $courses = DB::table('course_master')->get(['pk', 'course_name']);

            foreach ($courses as $cm) {
                $name = trim((string) $cm->course_name);
                if ($name === '') {
                    continue;
                }

                if (Schema::hasColumn('fc_ot_details', 'course_master_pk')) {
                    DB::table('fc_ot_details')
                        ->whereRaw('TRIM(course) = ?', [$name])
                        ->whereNull('course_master_pk')
                        ->update(['course_master_pk' => $cm->pk]);
                }

                if (Schema::hasColumn('fc_pre_history', 'course_master_pk')) {
                    DB::table('fc_pre_history')
                        ->whereRaw('TRIM(course) = ?', [$name])
                        ->whereNull('course_master_pk')
                        ->update(['course_master_pk' => $cm->pk]);
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('fc_ot_details', 'course_master_pk')) {
            Schema::table('fc_ot_details', function (Blueprint $table) {
                $table->dropColumn('course_master_pk');
            });
        }

        if (Schema::hasColumn('fc_pre_history', 'course_master_pk')) {
            Schema::table('fc_pre_history', function (Blueprint $table) {
                $table->dropColumn('course_master_pk');
            });
        }
    }
};
