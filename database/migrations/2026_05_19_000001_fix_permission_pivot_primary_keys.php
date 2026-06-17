<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('model_has_roles')) {
            $this->fixPivotPrimaryKey('model_has_roles', 'role_id');
        }

        if (Schema::hasTable('model_has_permissions')) {
            $this->fixPivotPrimaryKey('model_has_permissions', 'permission_id');
        }
    }

    /**
     * Spatie expects PRIMARY (pivot_id, model_id, model_type). Some DBs only had pivot_id as PK.
     */
    private function fixPivotPrimaryKey(string $table, string $pivotColumn): void
    {
        $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = 'PRIMARY'");
        $primaryCols = array_values(array_unique(array_column($indexes, 'Column_name')));

        if ($primaryCols === [$pivotColumn, 'model_id', 'model_type']) {
            return;
        }

        if ($primaryCols !== [$pivotColumn]) {
            return;
        }

        DB::statement(
            "ALTER TABLE `{$table}` DROP PRIMARY KEY, ADD PRIMARY KEY (`{$pivotColumn}`, `model_id`, `model_type`)"
        );
    }

    public function down(): void
    {
        // Intentionally left empty — do not restore the incorrect single-column primary key.
    }
};
