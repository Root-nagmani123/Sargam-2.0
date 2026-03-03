<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
     * Requester (requested by) - from employee_master.
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'employee_master_pk', 'pk');
    }

    /**
     * Approver (approved by) - employees_pk points to employee_master in this codebase.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'employees_pk', 'pk');
    }
}
