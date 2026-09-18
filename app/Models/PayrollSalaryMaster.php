<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Also read directly (via DB::table) by App\Http\Controllers\Admin\EstateController for
 * salary-grade based house eligibility — keep employee_master_pk / salary_grade_pk semantics
 * (Member wizard Step 6 writes employee_master.pk here, matching new/non-legacy records).
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

    public function employee()
    {
        return $this->belongsTo(EmployeeMaster::class, 'employee_master_pk', 'pk');
    }

    public function salaryGrade()
    {
        return $this->belongsTo(SalaryGrade::class, 'salary_grade_pk', 'pk');
    }

    public function employeeCategory()
    {
        return $this->belongsTo(EmployeeCategoryMaster::class, 'employee_category_master_pk', 'pk');
    }
}
