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

    // pk has no AUTO_INCREMENT in this table — callers must assign it (see MemberController::saveStep6PayrollData()).
    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'pk',
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
