<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Composite indexes for Mess stock/report aggregations and AvailableQuantityService.
 * Targets: Stock Summary, Stock Balance, Low Stock, Item Report, Purchase Details, store allocations.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mess_purchase_orders')) {
            Schema::table('mess_purchase_orders', function (Blueprint $table) {
                if (! $this->indexExists('mess_purchase_orders', 'idx_mpo_status_po_date_store')) {
                    $table->index(
                        ['status', 'po_date', 'store_id'],
                        'idx_mpo_status_po_date_store'
                    );
                }
            });
        }

        if (Schema::hasTable('mess_purchase_order_items')) {
            Schema::table('mess_purchase_order_items', function (Blueprint $table) {
                if (! $this->indexExists('mess_purchase_order_items', 'idx_mpoi_subcategory_po')) {
                    $table->index(
                        ['item_subcategory_id', 'purchase_order_id'],
                        'idx_mpoi_subcategory_po'
                    );
                }
            });
        }

        if (Schema::hasTable('mess_store_allocations')) {
            Schema::table('mess_store_allocations', function (Blueprint $table) {
                if (! $this->indexExists('mess_store_allocations', 'idx_msa_substore_alloc_date')) {
                    $table->index(
                        ['sub_store_id', 'allocation_date'],
                        'idx_msa_substore_alloc_date'
                    );
                }
            });
        }

        if (Schema::hasTable('mess_store_allocation_items')) {
            Schema::table('mess_store_allocation_items', function (Blueprint $table) {
                if (! $this->indexExists('mess_store_allocation_items', 'idx_msai_subcategory_alloc')) {
                    $table->index(
                        ['item_subcategory_id', 'store_allocation_id'],
                        'idx_msai_subcategory_alloc'
                    );
                }
            });
        }

        if (Schema::hasTable('sv_date_range_reports') && Schema::hasColumn('sv_date_range_reports', 'store_type')) {
            Schema::table('sv_date_range_reports', function (Blueprint $table) {
                if (! $this->indexExists('sv_date_range_reports', 'idx_svr_store_type')) {
                    $table->index(
                        ['store_id', 'store_type'],
                        'idx_svr_store_type'
                    );
                }
            });
        }

        if (Schema::hasTable('sv_date_range_report_items')) {
            Schema::table('sv_date_range_report_items', function (Blueprint $table) {
                if (! $this->indexExists('sv_date_range_report_items', 'idx_svdri_subcategory_report')) {
                    $table->index(
                        ['item_subcategory_id', 'sv_date_range_report_id'],
                        'idx_svdri_subcategory_report'
                    );
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('mess_purchase_orders')) {
            Schema::table('mess_purchase_orders', function (Blueprint $table) {
                if ($this->indexExists('mess_purchase_orders', 'idx_mpo_status_po_date_store')) {
                    $table->dropIndex('idx_mpo_status_po_date_store');
                }
            });
        }

        if (Schema::hasTable('mess_purchase_order_items')) {
            Schema::table('mess_purchase_order_items', function (Blueprint $table) {
                if ($this->indexExists('mess_purchase_order_items', 'idx_mpoi_subcategory_po')) {
                    $table->dropIndex('idx_mpoi_subcategory_po');
                }
            });
        }

        if (Schema::hasTable('mess_store_allocations')) {
            Schema::table('mess_store_allocations', function (Blueprint $table) {
                if ($this->indexExists('mess_store_allocations', 'idx_msa_substore_alloc_date')) {
                    $table->dropIndex('idx_msa_substore_alloc_date');
                }
            });
        }

        if (Schema::hasTable('mess_store_allocation_items')) {
            Schema::table('mess_store_allocation_items', function (Blueprint $table) {
                if ($this->indexExists('mess_store_allocation_items', 'idx_msai_subcategory_alloc')) {
                    $table->dropIndex('idx_msai_subcategory_alloc');
                }
            });
        }

        if (Schema::hasTable('sv_date_range_reports')) {
            Schema::table('sv_date_range_reports', function (Blueprint $table) {
                if ($this->indexExists('sv_date_range_reports', 'idx_svr_store_type')) {
                    $table->dropIndex('idx_svr_store_type');
                }
            });
        }

        if (Schema::hasTable('sv_date_range_report_items')) {
            Schema::table('sv_date_range_report_items', function (Blueprint $table) {
                if ($this->indexExists('sv_date_range_report_items', 'idx_svdri_subcategory_report')) {
                    $table->dropIndex('idx_svdri_subcategory_report');
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
