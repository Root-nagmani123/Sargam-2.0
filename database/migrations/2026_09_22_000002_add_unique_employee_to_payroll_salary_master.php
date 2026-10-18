<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Independent review of PR #319, F-006 (concurrency).
 *
 * saveStep6PayrollData() looked the row up with where('employee_master_pk', ...)->first()
 * and then either updated it or inserted a new one. Those are two statements with no
 * unique key to arbitrate between them, and the surrounding DB::transaction() does not
 * help: under MySQL's default REPEATABLE READ a plain SELECT takes no lock, so two
 * concurrent saves for the same employee can both read "no row" and both insert.
 *
 * Once two rows exist every later read is ->first() again, and payroll_salary_master is
 * joined by the Estate module on salary_grade_pk for house eligibility — so a duplicate
 * with a different grade decides eligibility from a grade nobody chose. Correcting that
 * after the fact is a data fix, not a code fix, which is why the constraint goes in.
 *
 * The existing `emppk` index leads on employee_master_pk but is Non_unique=1, so it does
 * not enforce anything (read from SHOW INDEX, not assumed).
 *
 * Guarded, and refuses rather than fails when the data cannot support the constraint:
 * if duplicates already exist the migration stops with a message naming them, so the
 * reconciliation is a deliberate DBA decision instead of a half-applied deploy. Verified
 * against the live table before writing this: 892 rows, 0 duplicate employee_master_pk.
 */
return new class extends Migration
{
    private const INDEX = 'payroll_salary_master_employee_master_pk_unique';

    private function indexExists(): bool
    {
        $row = DB::selectOne(
            "SELECT COUNT(*) AS c FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payroll_salary_master'
               AND INDEX_NAME = ?",
            [self::INDEX]
        );

        return $row && (int) $row->c > 0;
    }

    public function up(): void
    {
        if (! Schema::hasTable('payroll_salary_master') || $this->indexExists()) {
            return;
        }

        $duplicates = DB::table('payroll_salary_master')
            ->select('employee_master_pk', DB::raw('COUNT(*) AS row_count'))
            ->whereNotNull('employee_master_pk')
            ->groupBy('employee_master_pk')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isNotEmpty()) {
            $sample = $duplicates->take(10)
                ->map(fn ($d) => "{$d->employee_master_pk} ({$d->row_count} rows)")
                ->implode(', ');

            throw new RuntimeException(
                'Cannot add a unique index on payroll_salary_master.employee_master_pk: '
                . $duplicates->count() . ' employee(s) already have more than one payroll row. '
                . 'Reconcile these first (DBA), then re-run: ' . $sample
            );
        }

        DB::statement(
            'ALTER TABLE payroll_salary_master ADD UNIQUE ' . self::INDEX . ' (employee_master_pk)'
        );
    }

    public function down(): void
    {
        if ($this->indexExists()) {
            DB::statement('ALTER TABLE payroll_salary_master DROP INDEX ' . self::INDEX);
        }
    }
};
