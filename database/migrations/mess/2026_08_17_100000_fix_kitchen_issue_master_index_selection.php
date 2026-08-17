<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fixes optimizer picking the single-column kitchen_issue_type index over the
 * composite idx_kim_type_issue_date_pk on the Selling Voucher listing query
 * (SQL performance review 17 Aug 2026 — see slow query log 2026-08-14).
 *
 * Step 1: ANALYZE TABLE refreshes stale statistics; usually enough on its own.
 * Step 2: only if the optimizer still avoids the composite index afterwards,
 * drop the redundant single-column indexes it's subsumed by.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kitchen_issue_master')) {
            return;
        }

        DB::statement('ANALYZE TABLE kitchen_issue_master');

        if ($this->indexExists('kitchen_issue_master', 'kitchen_issue_master_kitchen_issue_type_index')) {
            DB::statement('ALTER TABLE kitchen_issue_master DROP INDEX kitchen_issue_master_kitchen_issue_type_index');
        }

        if ($this->indexExists('kitchen_issue_master', 'kim_issue_date_index')) {
            DB::statement('ALTER TABLE kitchen_issue_master DROP INDEX kim_issue_date_index');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('kitchen_issue_master')) {
            return;
        }

        if (! $this->indexExists('kitchen_issue_master', 'kitchen_issue_master_kitchen_issue_type_index')) {
            DB::statement('ALTER TABLE kitchen_issue_master ADD INDEX kitchen_issue_master_kitchen_issue_type_index (kitchen_issue_type)');
        }

        if (! $this->indexExists('kitchen_issue_master', 'kim_issue_date_index')) {
            DB::statement('ALTER TABLE kitchen_issue_master ADD INDEX kim_issue_date_index (issue_date)');
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();
        $result = $connection->select(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$database, $table, $indexName]
        );

        return $result !== [];
    }
};
