<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

    /** @return \Illuminate\Support\Collection<int, object> the duplicated values and their counts */
    private function duplicates()
    {
        return DB::table(self::TABLE)
            ->selectRaw(sprintf('`%s` AS value, COUNT(*) AS occurrences', self::COLUMN))
            ->whereNotNull(self::COLUMN)
            ->where(self::COLUMN, '<>', '')
            ->groupBy(self::COLUMN)
            ->havingRaw('COUNT(*) > 1')
            ->get();
    }

    public function up(): void
    {
        if (! Schema::hasTable(self::TABLE) || ! Schema::hasColumn(self::TABLE, self::COLUMN)) {
            return;
        }

        if ($this->indexExists()) {
            return;
        }

        // Duplicates mean the index cannot be added. It must not mean the
        // release stops.
        //
        // Throwing here was the safer-looking choice - it refuses rather than
        // half-applying - but the thing it refuses is `php artisan migrate`, in
        // the middle of a deploy, over a condition nobody can see until the
        // deploy is already running. Merging two expertise rows is a data
        // decision someone has to take deliberately; it is not something to
        // take under pressure with a release half out of the door.
        //
        // So: skip the index, say so loudly, and name the offending values.
        // Nothing is half-applied - the index is either created or it is not -
        // and re-running the migration after the rows are merged adds it. The
        // uniqueness users actually experience is unaffected meanwhile: the
        // store path validates with Rule::unique()->ignore() and still catches
        // 1062, so the index is defence in depth rather than the only guard.
        $duplicates = $this->duplicates();

        if ($duplicates->isNotEmpty()) {
            $message = sprintf(
                '%s NOT created: %d duplicate %s value(s) in %s (%s). '
                . 'Merge or rename them and re-run this migration; '
                . 'application-level uniqueness is unaffected in the meantime.',
                self::INDEX,
                $duplicates->count(),
                self::COLUMN,
                self::TABLE,
                $duplicates->take(10)->map(
                    fn ($row) => sprintf('"%s" x%d', $row->value, $row->occurrences)
                )->implode(', ')
            );

            Log::warning($message);

            // Visible in the deploy log as well as the application log.
            if (PHP_SAPI === 'cli') {
                fwrite(STDERR, '[migration] ' . $message . PHP_EOL);
            }

            return;
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
