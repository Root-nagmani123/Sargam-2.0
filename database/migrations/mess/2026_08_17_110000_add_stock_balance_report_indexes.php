<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Composite indexes for Stock Balance Till Date aggregation (SQL performance
 * review 17 Aug 2026). buildStockBalanceTillDateData() filters
 * kitchen_issue_type + store_type + issue_date together, and
 * store_type + issue_date on sv_date_range_reports/report_items — neither
 * combination is fully covered by the existing indexes, so the optimizer
 * falls back to a partial index scan or full table scan on each.
 *
 * Additive only: no existing index is touched.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kitchen_issue_master')) {
            Schema::table('kitchen_issue_master', function (Blueprint $table) {
                if (! $this->indexExists('kitchen_issue_master', 'idx_kim_type_storetype_date_pk')) {
                    $table->index(
                        ['kitchen_issue_type', 'store_type', 'issue_date', 'pk'],
                        'idx_kim_type_storetype_date_pk'
                    );
                }
            });
        }

        if (Schema::hasTable('sv_date_range_reports')) {
            Schema::table('sv_date_range_reports', function (Blueprint $table) {
                if (! $this->indexExists('sv_date_range_reports', 'idx_svr_storetype_store_id')) {
                    $table->index(
                        ['store_type', 'store_id'],
                        'idx_svr_storetype_store_id'
                    );
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('kitchen_issue_master')) {
            Schema::table('kitchen_issue_master', function (Blueprint $table) {
                if ($this->indexExists('kitchen_issue_master', 'idx_kim_type_storetype_date_pk')) {
                    $table->dropIndex('idx_kim_type_storetype_date_pk');
                }
            });
        }

        if (Schema::hasTable('sv_date_range_reports')) {
            Schema::table('sv_date_range_reports', function (Blueprint $table) {
                if ($this->indexExists('sv_date_range_reports', 'idx_svr_storetype_store_id')) {
                    $table->dropIndex('idx_svr_storetype_store_id');
                }
            });
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
