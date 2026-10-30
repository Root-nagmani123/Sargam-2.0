<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PR #319 review, F-009: basic_pay was declared integer while every other money column
 * on payroll_salary_master is double(15,2) — net_salary, gross_salary, tds,
 * payable_amount, deduction_amount and tds_adjust (read from information_schema, not
 * assumed). It was the one money column on the table that could not represent paise, so
 * a value entered with a fractional part was rejected by the validator and, had the rule
 * been relaxed without changing the column, would have been truncated on write. This
 * table is read by the Estate module for house eligibility, so a figure that silently
 * disagrees with the employee's letter is a data problem, not a cosmetic one.
 *
 * The column is widened to match its siblings' precision so that it can represent
 * whatever the domain turns out to require. decimal(15,2) rather than double(15,2) —
 * same precision and range, but exact rather than binary floating point, which is the
 * correct choice for money and does not change how the column is read.
 *
 * This is NOT a recorded domain decision that basic pay carries paise; the Product
 * owner's confirmation is still owed. An earlier version of this docblock said
 * "Decision recorded", which the independent review raised as F-040. Widening is the
 * safe move either way: decimal(15,2) holds every value a signed INT could, so no
 * answer to the domain question makes this migration wrong.
 *
 * Widening only: decimal(15,2) holds every value a signed INT could. The sibling
 * migration 2026_08_19_000001 is corrected for environments where it has not run yet;
 * this one corrects environments where it already ran with the narrower type. Guarded on
 * DATA_TYPE rather than on a COLUMN_TYPE string literal — see F-035, where comparing
 * against 'bigint(20) unsigned' silently never matched because MySQL 8.0.19+ drops the
 * display width.
 */
return new class extends Migration
{
    private function currentDataType(): ?string
    {
        $column = DB::selectOne(
            "SELECT DATA_TYPE FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payroll_salary_master'
               AND COLUMN_NAME = 'basic_pay'"
        );

        return $column ? strtolower((string) $column->DATA_TYPE) : null;
    }

    public function up(): void
    {
        if (! Schema::hasColumn('payroll_salary_master', 'basic_pay')) {
            // The sibling migration has not run yet; its own corrected up() creates the
            // column as decimal(15,2) directly, so there is nothing to convert.
            return;
        }

        if ($this->currentDataType() === 'decimal') {
            return;
        }

        DB::statement(
            'ALTER TABLE payroll_salary_master MODIFY basic_pay DECIMAL(15,2) NULL'
        );
    }

    public function down(): void
    {
        // Intentionally a no-op. Narrowing back to integer is the defect this migration
        // exists to correct, and it would truncate the paise of any value written in the
        // meantime — an irreversible data loss on a money column.
    }
};
