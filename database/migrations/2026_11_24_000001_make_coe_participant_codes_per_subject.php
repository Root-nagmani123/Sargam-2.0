<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * COE Unique Participant Code: one code per OT per SUBJECT (was one per OT).
 *
 *   + subject_master_pk (subject_master.pk, bigint unsigned)
 *   unique (student, course, batch, subject)   replaces unique (student)
 *   unique (course, batch, subject, code)      replaces unique (code): a code names one OT
 *                                              within its subject's sheet; another subject
 *                                              (or course) may reuse it.
 *
 * DESTRUCTIVE, agreed 2026-11-24: the course-level codes cannot be assigned to a subject,
 * so every existing code is deleted, and the Unique Participant Codes faculty were given
 * on drive components (examination_drive_component_maps.faculty_unique_codes) are cleared
 * with them. Regenerate per subject afterwards. Production needs the same decision before
 * this runs there. Idempotent: a second run changes nothing.
 */
return new class extends Migration
{
    private const TABLE = 'coe_participant_codes';

    public function up(): void
    {
        if (! Schema::hasTable(self::TABLE) || Schema::hasColumn(self::TABLE, 'subject_master_pk')) {
            return;
        }

        DB::table(self::TABLE)->delete();
        if (Schema::hasColumn('examination_drive_component_maps', 'faculty_unique_codes')) {
            DB::table('examination_drive_component_maps')->whereNotNull('faculty_unique_codes')->update(['faculty_unique_codes' => null]);
        }

        Schema::table(self::TABLE, function (Blueprint $table) {
            $table->unsignedBigInteger('subject_master_pk')->after('course_master_pk');
        });

        // New keys first: coe_pc_student_fk needs an index that starts with student_master_pk,
        // which the new unique key provides once the old one is gone.
        Schema::table(self::TABLE, function (Blueprint $table) {
            $table->unique(['student_master_pk', 'course_master_pk', 'batch', 'subject_master_pk'], 'coe_pc_student_subject_unique');
            $table->unique(['course_master_pk', 'batch', 'subject_master_pk', 'unique_code'], 'coe_pc_subject_code_unique');
            $table->foreign('subject_master_pk', 'coe_pc_subject_fk')->references('pk')->on('subject_master');
        });

        Schema::table(self::TABLE, function (Blueprint $table) {
            $table->dropUnique('coe_pc_student_unique');
            $table->dropUnique('coe_pc_code_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn(self::TABLE, 'subject_master_pk')) {
            return;
        }

        // Per-subject codes cannot become one code per OT: they are removed.
        DB::table(self::TABLE)->delete();

        Schema::table(self::TABLE, function (Blueprint $table) {
            $table->unique('student_master_pk', 'coe_pc_student_unique');
            $table->unique('unique_code', 'coe_pc_code_unique');
        });

        Schema::table(self::TABLE, function (Blueprint $table) {
            $table->dropForeign('coe_pc_subject_fk');
            $table->dropUnique('coe_pc_subject_code_unique');
            $table->dropUnique('coe_pc_student_subject_unique');
            $table->dropColumn('subject_master_pk');
        });
    }
};
