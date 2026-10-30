<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // payroll_salary_master already exists (created outside Laravel migrations) and is read
        // by the Estate module — this only adds the one column the new Member wizard step needs.
        //
        // PR #319 re-review F-055: Schema::hasColumn() on a table that doesn't exist at all
        // returns false (not an exception), which previously fell through to Schema::table()
        // below and threw (SQLSTATE 42S02) on a genuinely fresh database. No migration in
        // this repo creates payroll_salary_master.
        if (! Schema::hasTable('payroll_salary_master')) {
            return;
        }

        if (! Schema::hasColumn('payroll_salary_master', 'basic_pay')) {
            Schema::table('payroll_salary_master', function (Blueprint $table) {
                // decimal(15,2), matching every other money column on this table
                // (net_salary, gross_salary, tds, payable_amount, deduction_amount,
                // tds_adjust). A plain integer() here could not represent paise and was
                // the only money column on the table that could not — PR #319 review,
                // F-009. Environments where this migration already ran with the narrower
                // type are corrected by 2026_09_22_000001.
                $table->decimal('basic_pay', 15, 2)->nullable()->after('employee_category_master_pk');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('payroll_salary_master', 'basic_pay')) {
            Schema::table('payroll_salary_master', function (Blueprint $table) {
                $table->dropColumn('basic_pay');
            });
        }
    }
};
