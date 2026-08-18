<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for the dashboard notice feed.
 *
 * `notices_notification` carries only a PRIMARY KEY (verified with SHOW INDEX on
 * the dev database), so every feed page load full-scans the table and filesorts
 * the result. The feed always filters on active_inactive + expiry_date and always
 * sorts by display_date DESC (see notice_feed_base_query() in app/helpers.php);
 * the Type and Target Audience dropdowns add equality filters on top.
 *
 * idx_nn_feed is ordered to serve that shape: the two constant-ish predicates
 * first, then the sort column, so MySQL can range-scan on expiry_date and read
 * display_date in order rather than sorting.
 *
 * There is no create migration for this table anywhere in the repo, so the live
 * schema is the only source of truth — every step here is guarded and this file
 * is a no-op on a database that does not have the table or already has the
 * indexes. Nothing is dropped or altered; indexes are only ever added.
 */
return new class extends Migration
{
    private const TABLE = 'notices_notification';

    private array $indexes = [
        'idx_nn_feed'     => '(active_inactive, expiry_date, display_date)',
        'idx_nn_type'     => '(notice_type)',
        'idx_nn_audience' => '(target_audience)',
    ];

    public function up(): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            return;
        }

        foreach ($this->indexes as $name => $columns) {
            if ($this->indexExists($name) || ! $this->columnsExist($columns)) {
                continue;
            }

            $table = self::TABLE;

            // ALGORITHM=INPLACE, LOCK=NONE keeps the table readable and writable
            // while the index builds (online DDL).
            try {
                DB::statement("ALTER TABLE {$table} ADD INDEX {$name} {$columns}, ALGORITHM=INPLACE, LOCK=NONE");
            } catch (\Throwable $e) {
                // Fall back to a plain add if this server rejects the explicit clause.
                DB::statement("ALTER TABLE {$table} ADD INDEX {$name} {$columns}");
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            return;
        }

        $table = self::TABLE;

        foreach (array_keys($this->indexes) as $name) {
            if ($this->indexExists($name)) {
                DB::statement("ALTER TABLE {$table} DROP INDEX {$name}");
            }
        }
    }

    private function indexExists(string $name): bool
    {
        return ! empty(DB::select(
            'SHOW INDEX FROM ' . self::TABLE . ' WHERE Key_name = ?',
            [$name]
        ));
    }

    /**
     * The column list is authored here, not user input, but the live schema is
     * unverified by any create migration — so confirm each column exists before
     * naming it in DDL.
     */
    private function columnsExist(string $columns): bool
    {
        foreach (explode(',', trim($columns, '()')) as $column) {
            if (! Schema::hasColumn(self::TABLE, trim($column))) {
                return false;
            }
        }

        return true;
    }
};
