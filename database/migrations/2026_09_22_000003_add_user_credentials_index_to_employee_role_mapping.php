<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Independent review of PR #319, F-008 (performance).
 *
 * employee_role_mapping carries PRIMARY(pk) and nothing else — read from
 * information_schema.STATISTICS, not assumed. Every access this wizard makes to the
 * table filters on user_credentials_pk, and PR #319 turned what used to be one
 * unindexed filtered DELETE into a SELECT plus a DELETE on the same column, in both
 * MemberController::update() and syncSpatieRolesFromWizardSelection()'s caller:
 *
 *     EmployeeRoleMapping::where('user_credentials_pk', ...)
 *         ->whereIn('user_role_master_pk', $offeredRoleIds)
 *
 * At 1339 rows (measured) the cost of a full scan is not observable, which is exactly
 * why the finding was Low rather than higher. It is fixed now because the table grows
 * as members x roles, the access pattern is now established in two places rather than
 * one, and the index is free to add while the table is small — the same change against
 * a large table is a locking decision instead of a one-line migration.
 *
 * Composite, leading on user_credentials_pk: that order serves the where() alone AND
 * the where()+whereIn() pair, whereas the reverse order serves neither well because
 * user_credentials_pk is the selective column here (one member's rows) while
 * user_role_master_pk repeats across every member.
 *
 * Guarded on information_schema rather than on a try/catch, so a re-run is a genuine
 * no-op instead of a swallowed error.
 */
return new class extends Migration
{
    private const TABLE = 'employee_role_mapping';
    private const INDEX = 'employee_role_mapping_user_credentials_pk_role_pk_index';

    private function indexExists(): bool
    {
        $row = DB::selectOne(
            "SELECT COUNT(*) AS c FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?",
            [self::TABLE, self::INDEX]
        );

        return $row && (int) $row->c > 0;
    }

    public function up(): void
    {
        if (! Schema::hasTable(self::TABLE) || $this->indexExists()) {
            return;
        }

        // Both columns are checked because this table predates the repository's
        // migrations and its exact shape is not guaranteed on every environment.
        if (! Schema::hasColumn(self::TABLE, 'user_credentials_pk')
            || ! Schema::hasColumn(self::TABLE, 'user_role_master_pk')) {
            return;
        }

        DB::statement(
            'ALTER TABLE ' . self::TABLE . ' ADD INDEX ' . self::INDEX
            . ' (user_credentials_pk, user_role_master_pk)'
        );
    }

    public function down(): void
    {
        if ($this->indexExists()) {
            DB::statement('ALTER TABLE ' . self::TABLE . ' DROP INDEX ' . self::INDEX);
        }
    }
};
