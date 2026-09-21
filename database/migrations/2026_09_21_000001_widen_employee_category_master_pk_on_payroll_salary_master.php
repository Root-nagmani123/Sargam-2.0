<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PR #319 review round 6 (F-034): the sibling migration
 * 2026_08_18_235900_add_employee_category_master_pk_to_payroll_salary_master
 * declared this column as a plain signed integer(), while the primary key it
 * references — employee_category_master.pk — is bigIncrements() (BIGINT
 * UNSIGNED). That migration is now fixed for environments where it hasn't
 * run yet; this migration corrects the type on environments (including this
 * one) where it already ran with the narrower type.
 *
 * Widening only — never narrows, never risks data loss. The column's only
 * existing values (checked against this environment: 5 non-null rows, all
 * small positive integers) fit comfortably in either type.
 */
return new class extends Migration
{
    private function isAlreadyWidened(): bool
    {
        $column = DB::selectOne(
            "SELECT COLUMN_TYPE FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payroll_salary_master'
               AND COLUMN_NAME = 'employee_category_master_pk'"
        );

        if (!$column) {
            return false;
        }

        // MySQL 8.0.19+ omits the integer display width from COLUMN_TYPE once a
        // column is created/altered without one explicitly requested, reporting
        // "bigint unsigned" rather than "bigint(20) unsigned" (PR #319 review,
        // F-035). Matching by substring instead of the exact literal keeps this
        // guard version-independent.
        $type = strtolower($column->COLUMN_TYPE);

        return str_starts_with($type, 'bigint') && str_contains($type, 'unsigned');
    }

    public function up(): void
    {
        if (!Schema::hasColumn('payroll_salary_master', 'employee_category_master_pk')) {
            // Nothing to widen — the sibling migration hasn't run yet, and
            // its own (now-corrected) up() will create the column with the
            // right type directly.
            return;
        }

        if ($this->isAlreadyWidened()) {
            return;
        }

        DB::statement(
            'ALTER TABLE payroll_salary_master MODIFY employee_category_master_pk BIGINT(20) UNSIGNED NULL'
        );
    }

    public function down(): void
    {
        // Intentionally a no-op: narrowing back to a signed int() is the
        // defect this migration exists to correct, not a state worth
        // restoring, and doing so risks truncating any value written above
        // the signed INT range in the meantime.
    }
};
