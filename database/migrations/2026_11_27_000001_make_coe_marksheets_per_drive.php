<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * COE marksheets: one per OT per EXAMINATION DRIVE (was one per OT per course), as Merit &
 * Ranking is now drive-wise (agreed 2026-11-27).
 *
 *   + examination_drive_id  bigint unsigned NULL, after course_master_pk
 *   unique (examination_drive_id, student_master_pk)   replaces unique (course_master_pk, student_master_pk)
 *   index  (course_master_pk)                          the marksheet serial still runs per course
 *
 * Existing rows (agreed): unpublished ones are deleted, they regenerate per drive on the next
 * listing; published ones stay as legacy course-wise marksheets (examination_drive_id NULL),
 * shown from their snapshot. Idempotent.
 */
return new class extends Migration
{
    private const TABLE = 'coe_marksheets';

    private function hasIndex(string $name): bool
    {
        return DB::select('SHOW INDEX FROM '.self::TABLE.' WHERE Key_name = ?', [$name]) !== [];
    }

    public function up(): void
    {
        if (! Schema::hasTable(self::TABLE) || Schema::hasColumn(self::TABLE, 'examination_drive_id')) {
            return;
        }

        // Published = 2 (App\Models\COE\Marksheet::STATUS_PUBLISHED).
        DB::table(self::TABLE)->where('status', '!=', 2)->delete();

        Schema::table(self::TABLE, function (Blueprint $table) {
            $table->unsignedBigInteger('examination_drive_id')->nullable()->after('course_master_pk');
            $table->unique(['examination_drive_id', 'student_master_pk'], 'coe_marksheets_drive_student_unique');
            $table->index('course_master_pk', 'coe_marksheets_course_index');
        });

        if ($this->hasIndex('coe_marksheets_course_master_pk_student_master_pk_unique')) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->dropUnique('coe_marksheets_course_master_pk_student_master_pk_unique');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn(self::TABLE, 'examination_drive_id')) {
            return;
        }

        // Per-drive marksheets cannot go back to one per course: the unpublished ones are removed.
        DB::table(self::TABLE)->whereNotNull('examination_drive_id')->where('status', '!=', 2)->delete();

        Schema::table(self::TABLE, function (Blueprint $table) {
            $table->unique(['course_master_pk', 'student_master_pk'], 'coe_marksheets_course_master_pk_student_master_pk_unique');
        });
        Schema::table(self::TABLE, function (Blueprint $table) {
            $table->dropUnique('coe_marksheets_drive_student_unique');
            $table->dropIndex('coe_marksheets_course_index');
            $table->dropColumn('examination_drive_id');
        });
    }
};
