<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Composite index for the Pre-Medical History report's row-selection join.
 *
 * That report cannot join fc_pre_history on user_id alone: the column carries a plain
 * (non-unique) index and the write path keys on (user_id, course_master_pk), so a trainee who
 * has been on two courses has two rows and a plain join would show them twice. The report
 * therefore picks one row per trainee with a correlated subquery:
 *
 *     select id from fc_pre_history
 *      where user_id = ? and (course_master_pk = ? or course_master_pk is null)
 *      order by (course_master_pk = ?) desc, id desc limit 1
 *
 * which runs once per driving row. This index covers exactly that lookup — equality on
 * user_id, then course_master_pk, with id trailing so the ordering is served from the index
 * instead of a filesort.
 *
 * NOT unique, deliberately. A unique (user_id, course_master_pk) would not constrain the six
 * legacy rows whose course_master_pk is NULL (MySQL permits repeated NULLs in a unique index),
 * so it would imply a guarantee it cannot make while risking a failed migration on existing
 * duplicates. The report's correctness comes from the query, not from this index.
 */
return new class extends Migration
{
    private const TABLE = 'fc_pre_history';

    private const INDEX = 'idx_fc_pre_history_user_course';

    public function up(): void
    {
        if (! $this->applicable() || $this->indexExists()) {
            return;
        }

        // ALGORITHM=INPLACE, LOCK=NONE keeps the table readable and writable while the index
        // builds (online DDL) — safe to run against a live database.
        try {
            DB::statement('ALTER TABLE `'.self::TABLE.'` ADD INDEX `'.self::INDEX.'` '
                .'(`user_id`, `course_master_pk`, `id`), ALGORITHM=INPLACE, LOCK=NONE');
        } catch (\Throwable $e) {
            // Fall back to a plain add if this server rejects the explicit clause.
            DB::statement('ALTER TABLE `'.self::TABLE.'` ADD INDEX `'.self::INDEX.'` '
                .'(`user_id`, `course_master_pk`, `id`)');
        }
    }

    public function down(): void
    {
        if ($this->applicable() && $this->indexExists()) {
            DB::statement('ALTER TABLE `'.self::TABLE.'` DROP INDEX `'.self::INDEX.'`');
        }
    }

    /** Every column must exist — a deployment without them simply skips this migration. */
    private function applicable(): bool
    {
        return Schema::hasTable(self::TABLE)
            && Schema::hasColumn(self::TABLE, 'user_id')
            && Schema::hasColumn(self::TABLE, 'course_master_pk');
    }

    private function indexExists(): bool
    {
        return ! empty(DB::select(
            'SHOW INDEX FROM `'.self::TABLE.'` WHERE Key_name = ?',
            [self::INDEX]
        ));
    }
};
