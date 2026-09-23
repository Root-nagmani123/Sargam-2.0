<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * payroll_salary_master already exists (created outside Laravel migrations,
 * also read directly by the Estate module). On environments seeded from a
 * production-like dump it already carries employee_category_master_pk; on a
 * fresh environment it does not, and nothing creates it. This migration adds
 * it, guarded to be a no-op wherever it already exists.
 *
 * Ordered (by filename timestamp) before
 * 2026_08_19_000001_add_basic_pay_to_payroll_salary_master, whose
 * ->after('employee_category_master_pk') anchor depends on this column
 * existing first.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('payroll_salary_master', 'employee_category_master_pk')) {
            return;
        }

        Schema::table('payroll_salary_master', function (Blueprint $table) {
            // Matches employee_category_master.pk (bigIncrements — BIGINT UNSIGNED).
            // A plain signed integer() here would work today (the referenced
            // table only ever holds small positive ids) but silently diverges
            // from the key it points at (PR #319 review, F-034); widening
            // costs nothing since no FK constraint exists to complain either way.
            $table->unsignedBigInteger('employee_category_master_pk')->nullable()->after('salary_grade_pk');
        });
    }

    public function down(): void
    {
        // Never drop this column: on a fresh environment where this
        // migration created it, it would be empty and safe to drop, but on
        // an environment where it already existed (production) it may hold
        // real values, and a migration can't tell those two cases apart at
        // rollback time. Same reasoning as the sibling table-creation
        // migration.
    }
};
