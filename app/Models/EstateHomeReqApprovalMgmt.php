<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class EstateHomeReqApprovalMgmt extends Model
{
    protected $table = 'estate_home_req_approval_mgmt';

    public $timestamps = false;

    protected $primaryKey = 'pk';

    protected $fillable = [
        'employee_master_pk',
        'employees_pk',
        'is_forword',
    ];

    protected $casts = [
        'is_forword' => 'integer',
    ];

    /**
     * Estate module uses employee_master.pk_old when present (payroll_salary_master references pk_old).
     */
    protected static function employeeOwnerKey(): string
    {
        return Schema::hasColumn((new EmployeeMaster)->getTable(), 'pk_old') ? 'pk_old' : 'pk';
    }

    /**
     * Requester (requested by) - from employee_master.
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'employee_master_pk', static::employeeOwnerKey());
    }

    /**
     * Approver (approved by) - employees_pk points to employee_master in this codebase.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'employees_pk', static::employeeOwnerKey());
    }
}
