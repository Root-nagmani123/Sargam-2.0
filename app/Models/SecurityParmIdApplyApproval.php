<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Maps to security table: security_parm_id_apply_approval
 * status: 1 = Approval I, 2 = Approval II, 3 = Rejected
 */
class SecurityParmIdApplyApproval extends Model
{
    protected $table = 'security_parm_id_apply_approval';

    public $timestamps = false;

    protected $fillable = [
        'security_parm_id_apply_pk',
        'status',
        'approval_remarks',
        'recommend_status',
        'approval_emp_pk',
        'created_by',
        'created_date',
        'modified_by',
        'modified_date',
    ];

    protected $casts = [
        'created_date' => 'datetime',
        'modified_date' => 'datetime',
    ];

    const STATUS_APPROVAL_1 = 1;
    const STATUS_APPROVAL_2 = 2;
    const STATUS_REJECTED = 3;

    public function securityParmIdApply()
    {
        return $this->belongsTo(SecurityParmIdApply::class, 'security_parm_id_apply_pk', 'emp_id_apply');
    }

    public function approver()
    {
        return $this->belongsTo(EmployeeMaster::class, 'approval_emp_pk', 'pk');
    }
}
