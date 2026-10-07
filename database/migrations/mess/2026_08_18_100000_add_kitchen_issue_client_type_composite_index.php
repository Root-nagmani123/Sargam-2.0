<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Composite index for the mess bill payment-allocation queries in
 * ProcessMessBillsEmployeeController::resolveBuyerBillsForPaymentAllocation()
 * and preloadBuyerBillsForAllocation() (SQL performance review 18 Aug 2026).
 *
 * Both filter by kitchen_issue_type + client_type and sort by issue_date;
 * idx_kim_type_issue_date_pk (added 21 Jul 2026) covers kitchen_issue_type +
 * issue_date but not client_type, so the optimizer still row-scans within
 * the kitchen_issue_type range to apply the client_type filter. This index
 * lets it seek on all three narrowing columns before the sort.
 *
 * Additive only: no existing index is touched.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kitchen_issue_master')) {
            return;
        }

        Schema::table('kitchen_issue_master', function (Blueprint $table) {
            if (! $this->indexExists('kitchen_issue_master', 'idx_kim_type_client_type_issue_pk')) {
                $table->index(
                    ['kitchen_issue_type', 'client_type', 'issue_date', 'pk'],
                    'idx_kim_type_client_type_issue_pk'
                );
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('kitchen_issue_master')) {
            return;
        }

        Schema::table('kitchen_issue_master', function (Blueprint $table) {
            if ($this->indexExists('kitchen_issue_master', 'idx_kim_type_client_type_issue_pk')) {
                $table->dropIndex('idx_kim_type_client_type_issue_pk');
            }
        });
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
