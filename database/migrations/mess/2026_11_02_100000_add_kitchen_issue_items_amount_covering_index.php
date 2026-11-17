<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Covering index for the kitchen-issue half of the Process Mess Bills buyer paid pool
 * (fetchBuyerPaidPoolByGroupKey): its line-amount SUM over kitchen_issue_items reads
 * quantity / return_quantity / rate from the index instead of the rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kitchen_issue_items')
            && ! $this->indexExists('kitchen_issue_items', 'idx_kii_master_amounts')) {
            Schema::table('kitchen_issue_items', function (Blueprint $table) {
                $table->index(
                    ['kitchen_issue_master_pk', 'quantity', 'return_quantity', 'rate'],
                    'idx_kii_master_amounts'
                );
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('kitchen_issue_items')
            && $this->indexExists('kitchen_issue_items', 'idx_kii_master_amounts')) {
            Schema::table('kitchen_issue_items', function (Blueprint $table) {
                $table->dropIndex('idx_kii_master_amounts');
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
