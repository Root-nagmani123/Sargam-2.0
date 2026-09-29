<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Index the columns the Group Mapping OT-code lookup joins and filters on.
 *
 * student_master_course__map carried nothing but its primary key, so
 * GroupMappingController::resolveEnrolledStudent() drove a full table scan:
 *
 *   EXPLAIN … table=smcm  type=ALL  key=NULL  rows=2108
 *
 * That query runs from the Add Student modal on a 400 ms keystroke debounce, so one
 * admin typing one OT code triggers several scans. At ~2k rows nothing falls over
 * today — this is not urgent — but the table grows by a row per student-course
 * enrolment and the access pattern is high-frequency by design.
 *
 * Column order is (course_master_pk, active_inactive, student_master_pk): the course
 * and the active flag are the equality predicates, and student_master_pk then covers
 * the join so the row can be satisfied from the index.
 */
return new class extends Migration
{
    private const TABLE = 'student_master_course__map';
    private const INDEX = 'smcm_course_active_student_index';

    public function up(): void
    {
        if (! Schema::hasTable(self::TABLE) || $this->indexExists()) {
            return;
        }

        Schema::table(self::TABLE, function ($table) {
            $table->index(
                ['course_master_pk', 'active_inactive', 'student_master_pk'],
                self::INDEX
            );
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::TABLE) || ! $this->indexExists()) {
            return;
        }

        Schema::table(self::TABLE, function ($table) {
            $table->dropIndex(self::INDEX);
        });
    }

    /** Idempotent both ways — the table is legacy and not under migration control elsewhere. */
    private function indexExists(): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', self::TABLE)
            ->where('index_name', self::INDEX)
            ->exists();
    }
};
