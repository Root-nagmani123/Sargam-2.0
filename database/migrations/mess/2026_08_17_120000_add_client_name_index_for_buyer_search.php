<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index for client_name on kitchen_issue_master / sv_date_range_reports
 * (SQL performance review 17 Aug 2026).
 *
 * NOT a fix for ProcessMessBillsEmployeeController::getSummaryStats(): that method
 * searches client_name with a leading-wildcard "LIKE '%text%'" substring match,
 * which this (or any btree) index cannot serve. A "try prefix match first"
 * optimization was attempted there and reverted, because it silently dropped
 * valid matches whenever the search term appeared mid-string rather than at the
 * start (e.g. searching "CONFRENCE" missed rows like "IST CONFRENCE") — a real
 * fix needs FULLTEXT search, which is out of scope here.
 *
 * This index instead speeds up other exact/prefix lookups on client_name (e.g.
 * MessBuyerClientFilter's `client_name = ?` / `client_name LIKE 'text (%'`
 * patterns) and is groundwork for a future FULLTEXT migration.
 *
 * Additive only: no existing index is touched.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kitchen_issue_master')) {
            Schema::table('kitchen_issue_master', function (Blueprint $table) {
                if (! $this->indexExists('kitchen_issue_master', 'kitchen_issue_master_client_name_index')) {
                    $table->index('client_name', 'kitchen_issue_master_client_name_index');
                }
            });
        }

        if (Schema::hasTable('sv_date_range_reports')) {
            Schema::table('sv_date_range_reports', function (Blueprint $table) {
                if (! $this->indexExists('sv_date_range_reports', 'sv_date_range_reports_client_name_index')) {
                    $table->index('client_name', 'sv_date_range_reports_client_name_index');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('kitchen_issue_master')) {
            Schema::table('kitchen_issue_master', function (Blueprint $table) {
                if ($this->indexExists('kitchen_issue_master', 'kitchen_issue_master_client_name_index')) {
                    $table->dropIndex('kitchen_issue_master_client_name_index');
                }
            });
        }

        if (Schema::hasTable('sv_date_range_reports')) {
            Schema::table('sv_date_range_reports', function (Blueprint $table) {
                if ($this->indexExists('sv_date_range_reports', 'sv_date_range_reports_client_name_index')) {
                    $table->dropIndex('sv_date_range_reports_client_name_index');
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
