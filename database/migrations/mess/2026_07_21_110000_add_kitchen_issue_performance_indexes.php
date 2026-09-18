<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Composite indexes for Kitchen Issue aggregation + listing (SQL performance review 20 Jul 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kitchen_issue_master')) {
            Schema::table('kitchen_issue_master', function (Blueprint $table) {
                if (! $this->indexExists('kitchen_issue_master', 'idx_kim_store_type_issue_pk')) {
                    $table->index(
                        ['store_id', 'store_type', 'kitchen_issue_type', 'pk'],
                        'idx_kim_store_type_issue_pk'
                    );
                }
                if (! $this->indexExists('kitchen_issue_master', 'idx_kim_type_issue_date_pk')) {
                    $table->index(
                        ['kitchen_issue_type', 'issue_date', 'pk'],
                        'idx_kim_type_issue_date_pk'
                    );
                }
            });
        }

        if (Schema::hasTable('kitchen_issue_items')) {
            Schema::table('kitchen_issue_items', function (Blueprint $table) {
                if (! $this->indexExists('kitchen_issue_items', 'idx_kii_master_subcategory')) {
                    $table->index(
                        ['kitchen_issue_master_pk', 'item_subcategory_id'],
                        'idx_kii_master_subcategory'
                    );
                }
                if (! $this->indexExists('kitchen_issue_items', 'idx_kii_master_pk')) {
                    $table->index(
                        ['kitchen_issue_master_pk', 'pk'],
                        'idx_kii_master_pk'
                    );
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('kitchen_issue_master')) {
            Schema::table('kitchen_issue_master', function (Blueprint $table) {
                if ($this->indexExists('kitchen_issue_master', 'idx_kim_store_type_issue_pk')) {
                    $table->dropIndex('idx_kim_store_type_issue_pk');
                }
                if ($this->indexExists('kitchen_issue_master', 'idx_kim_type_issue_date_pk')) {
                    $table->dropIndex('idx_kim_type_issue_date_pk');
                }
            });
        }

        if (Schema::hasTable('kitchen_issue_items')) {
            Schema::table('kitchen_issue_items', function (Blueprint $table) {
                if ($this->indexExists('kitchen_issue_items', 'idx_kii_master_subcategory')) {
                    $table->dropIndex('idx_kii_master_subcategory');
                }
                if ($this->indexExists('kitchen_issue_items', 'idx_kii_master_pk')) {
                    $table->dropIndex('idx_kii_master_pk');
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
