<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Also read directly (via DB::table) by App\Http\Controllers\Admin\EstateController for
 * salary-grade based house eligibility — keep employee_master_pk / salary_grade_pk semantics.
 *
 * Despite its name, employee_master_pk holds the employee's LEGACY key: Member wizard Step 6
 * writes employee_master.pk_old when the employee has one, and falls back to
 * employee_master.pk only when pk_old is NULL/0 — see MemberController::payrollEmployeeKey(),
 * which mirrors Estate's own join (PR #319 review F-037; docblock corrected in re-review F-071).
 */
class PayrollSalaryMaster extends Model
{
    protected $table = 'payroll_salary_master';

    protected $primaryKey = 'pk';

    // pk now has AUTO_INCREMENT (see 2026_09_18_000001_add_auto_increment_to_payroll_salary_master_pk) —
    // Eloquent assigns it; callers must not set 'pk' manually.
    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'employee_master_pk',
        'salary_grade_pk',
        'employee_category_master_pk',
        'basic_pay',
        'bank_name',
        'account_no',
    ];

    // No employee() relation on purpose (PR #319 re-review F-074): employee_master_pk holds
    // pk_old for legacy employees (see the class docblock), so a belongsTo on pk silently
    // misses them. Resolve the key with MemberController::payrollEmployeeKey() instead.

    public function salaryGrade()
    {
        return $this->belongsTo(SalaryGrade::class, 'salary_grade_pk', 'pk');
    }

    public function employeeCategory()
    {
        return $this->belongsTo(EmployeeCategoryMaster::class, 'employee_category_master_pk', 'pk');
    }
}
