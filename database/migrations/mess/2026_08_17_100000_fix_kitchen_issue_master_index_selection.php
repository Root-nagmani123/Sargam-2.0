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
 * Step 2: drop kitchen_issue_type-only index — every caller that filters on
 * kitchen_issue_type also uses it as their leading predicate, so it's fully
 * subsumed by the composite [kitchen_issue_type, issue_date, pk] index.
 *
 * kim_issue_date_index (issue_date alone) is kept: KitchenIssueController::
 * getKitchenIssueRecords() filters/sorts on issue_date without a
 * kitchen_issue_type predicate, so it is not covered by the composite index
 * and still needs the standalone index (code review PR #301, trap #25).
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
    }

    public function down(): void
    {
        if (! Schema::hasTable('kitchen_issue_master')) {
            return;
        }

        if (! $this->indexExists('kitchen_issue_master', 'kitchen_issue_master_kitchen_issue_type_index')) {
            DB::statement('ALTER TABLE kitchen_issue_master ADD INDEX kitchen_issue_master_kitchen_issue_type_index (kitchen_issue_type)');
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
