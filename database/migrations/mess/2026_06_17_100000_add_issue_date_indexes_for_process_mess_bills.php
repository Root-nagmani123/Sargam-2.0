<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sv_date_range_report_items', function (Blueprint $table) {
            if (! $this->indexExists('sv_date_range_report_items', 'sv_drr_items_issue_date_index')) {
                $table->index('issue_date', 'sv_drr_items_issue_date_index');
            }
            if (! $this->indexExists('sv_date_range_report_items', 'sv_drr_items_report_issue_date_index')) {
                $table->index(
                    ['sv_date_range_report_id', 'issue_date'],
                    'sv_drr_items_report_issue_date_index'
                );
            }
        });

        if (Schema::hasTable('kitchen_issue_master') && Schema::hasColumn('kitchen_issue_master', 'issue_date')) {
            Schema::table('kitchen_issue_master', function (Blueprint $table) {
                if (! $this->indexExists('kitchen_issue_master', 'kim_issue_date_index')) {
                    $table->index('issue_date', 'kim_issue_date_index');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('sv_date_range_report_items', function (Blueprint $table) {
            if ($this->indexExists('sv_date_range_report_items', 'sv_drr_items_issue_date_index')) {
                $table->dropIndex('sv_drr_items_issue_date_index');
            }
            if ($this->indexExists('sv_date_range_report_items', 'sv_drr_items_report_issue_date_index')) {
                $table->dropIndex('sv_drr_items_report_issue_date_index');
            }
        });

        if (Schema::hasTable('kitchen_issue_master')) {
            Schema::table('kitchen_issue_master', function (Blueprint $table) {
                if ($this->indexExists('kitchen_issue_master', 'kim_issue_date_index')) {
                    $table->dropIndex('kim_issue_date_index');
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
