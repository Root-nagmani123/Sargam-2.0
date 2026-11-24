<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * COE roll numbers: one per OT per COURSE run (was one per OT across the academy).
 *
 * An OT on FC and later on Phase-I holds one roll number on each, the same for every
 * subject of that course. A run is one course + batch (coe_roll_number_allocations), and
 * an OT is in one batch of a course, so unique (allocation, student) is one per course.
 *
 *   unique (roll_number_allocation_pk, student_master_pk)  replaces unique (student_master_pk)
 *   index  (student_master_pk)                             keeps coe_rn_student_master_pk_foreign indexed
 *   unique (roll_number) is kept: a roll number value still names one OT academy-wide.
 *
 * Additive for the data: every existing row already satisfies the new key. Idempotent.
 */
return new class extends Migration
{
    private const TABLE = 'coe_roll_numbers';

    private function hasIndex(string $name): bool
    {
        return DB::select('SHOW INDEX FROM ' . self::TABLE . ' WHERE Key_name = ?', [$name]) !== [];
    }

    public function up(): void
    {
        if (! Schema::hasTable(self::TABLE) || ! $this->hasIndex('coe_rn_student_unique')) {
            return;
        }

        // New keys first: the student foreign key needs an index on student_master_pk.
        Schema::table(self::TABLE, function (Blueprint $table) {
            $table->unique(['roll_number_allocation_pk', 'student_master_pk'], 'coe_rn_allocation_student_unique');
            $table->index('student_master_pk', 'coe_rn_student_index');
        });

        Schema::table(self::TABLE, function (Blueprint $table) {
            $table->dropUnique('coe_rn_student_unique');
        });
    }

    public function down(): void
    {
        if ($this->hasIndex('coe_rn_student_unique')) {
            return;
        }

        // Fails, as it should, while any OT holds roll numbers on two courses.
        Schema::table(self::TABLE, function (Blueprint $table) {
            $table->unique('student_master_pk', 'coe_rn_student_unique');
        });

        Schema::table(self::TABLE, function (Blueprint $table) {
            $table->dropIndex('coe_rn_student_index');
            $table->dropUnique('coe_rn_allocation_student_unique');
        });
    }
};
