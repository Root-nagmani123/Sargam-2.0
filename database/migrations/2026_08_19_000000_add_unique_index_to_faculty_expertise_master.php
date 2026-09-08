<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Back the expertise-name uniqueness rule with a database constraint.
 *
 * FacultyExpertiseMasterController::store() enforces uniqueness with
 * Rule::unique(...), which is a read followed by an unguarded write: two
 * administrators submitting the same new name in the same instant, or one
 * double-clicking Add, both pass validation and create two rows. The table
 * currently carries exactly one index — PRIMARY(pk) — confirmed by
 * `SHOW INDEX FROM faculty_expertise_master` against the live schema, not by
 * reading a create migration (there isn't one in this repository).
 *
 * Safe to apply: the table held 10 rows with 0 duplicate and 0 null/empty
 * `expertise_name` values when this was written. MySQL permits repeated NULLs
 * under a UNIQUE index, so rows that never had a name stay legal.
 *
 * Guarded and idempotent throughout — this runs against environments whose
 * schema history is unreliable, so it must survive being re-run and must never
 * fail a deploy because something is already there or is not there at all.
 */
return new class extends Migration
{
    private const TABLE = 'faculty_expertise_master';
    private const COLUMN = 'expertise_name';
    private const INDEX = 'fem_expertise_name_unique';

    private function indexExists(): bool
    {
        // information_schema rather than SHOW INDEX: it answers for a named index
        // directly and returns an empty set instead of throwing on a missing table.
        return DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', self::TABLE)
            ->where('INDEX_NAME', self::INDEX)
            ->exists();
    }

    private function duplicateCount(): int
    {
        return DB::table(self::TABLE)
            ->select(self::COLUMN)
            ->whereNotNull(self::COLUMN)
            ->where(self::COLUMN, '<>', '')
            ->groupBy(self::COLUMN)
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();
    }

    public function up(): void
    {
        if (! Schema::hasTable(self::TABLE) || ! Schema::hasColumn(self::TABLE, self::COLUMN)) {
            return;
        }

        if ($this->indexExists()) {
            return;
        }

        // Refuse rather than fail half-way: adding the index over duplicates
        // aborts with a driver error that reads as a broken deploy. Say what is
        // wrong and let a human merge the rows first.
        if (($duplicates = $this->duplicateCount()) > 0) {
            throw new RuntimeException(sprintf(
                'Cannot add %s: %d duplicate %s value(s) exist in %s. '
                . 'Merge or rename them, then re-run this migration.',
                self::INDEX,
                $duplicates,
                self::COLUMN,
                self::TABLE
            ));
        }

        DB::statement(sprintf(
            'ALTER TABLE `%s` ADD UNIQUE INDEX `%s` (`%s`)',
            self::TABLE,
            self::INDEX,
            self::COLUMN
        ));
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::TABLE) || ! $this->indexExists()) {
            return;
        }

        DB::statement(sprintf('ALTER TABLE `%s` DROP INDEX `%s`', self::TABLE, self::INDEX));
    }
};
