<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Index the two columns the CENTCOM issue search joins on.
 *
 * Why this is needed even though 2026_01_22_000006_create_issue_log_sub_category_map_table
 * already declares these indexes: that migration has never run against the live
 * database — the table is not in the `migrations` table, its columns are `int`
 * rather than `unsignedBigInteger`, `sub_category_name` is varchar(500) not 255,
 * and it carries neither the three foreign keys nor the three indexes the file
 * claims. The live table has exactly one index: PRIMARY(pk). So the create
 * migration cannot be trusted as a description of production, and this one is
 * written to be safe against whatever is actually there.
 *
 * What it buys: IssueManagementController's issue search resolves sub-category
 * matches to a pk list and feeds them to whereIn(), specifically because a
 * correlated EXISTS over the unindexed map table measured 3,154ms against 49ms
 * for the id-list form. These indexes remove that cliff and keep the id-list
 * path fast as the map grows past its current 699 rows.
 *
 * Every step is guarded and idempotent: this runs against environments whose
 * schema history is unreliable, so it must be safe to re-run and must never
 * fail a deploy because something already exists.
 */
return new class extends Migration
{
    private const TABLE = 'issue_log_sub_category_map';

    /** @var array<string, string> index name => column */
    private const INDEXES = [
        'ilscm_sub_category_pk_idx' => 'issue_sub_category_master_pk',
        'ilscm_issue_log_pk_idx' => 'issue_log_management_pk',
    ];

    private function indexExists(string $name): bool
    {
        // information_schema rather than SHOW INDEX: it answers for a named
        // index directly and returns an empty set instead of throwing when the
        // table is absent.
        return DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', self::TABLE)
            ->where('INDEX_NAME', $name)
            ->exists();
    }

    public function up(): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            return;
        }

        foreach (self::INDEXES as $name => $column) {
            if (! Schema::hasColumn(self::TABLE, $column) || $this->indexExists($name)) {
                continue;
            }

            DB::statement(sprintf(
                'ALTER TABLE `%s` ADD INDEX `%s` (`%s`)',
                self::TABLE,
                $name,
                $column
            ));
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            return;
        }

        foreach (array_keys(self::INDEXES) as $name) {
            if ($this->indexExists($name)) {
                DB::statement(sprintf('ALTER TABLE `%s` DROP INDEX `%s`', self::TABLE, $name));
            }
        }
    }
};
