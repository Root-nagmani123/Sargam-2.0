<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Case 7 - Vehicle Pass Duplicate Approval.
 * Table: vehicle_pass_duplicate_apply_approval_TWFW (exact SQL structure)
 * FK: vehicle_TW_pk -> vehicle_pass_duplicate_apply_TWFW.vehicle_tw_pk
 * status: 0=Pending, 2=Approved, 3=Rejected
 */
class VehiclePassDuplicateApplyApprovalTwfw extends Model
{
    protected $table = 'vehicle_pass_duplicate_apply_approval_TWFW';

    public $timestamps = false;

    const STATUS_PENDING = 0;

    const STATUS_APPROVED = 2;

    const STATUS_REJECTED = 3;

    protected $fillable = [
        'vehicle_TW_pk',
        'status',
        'veh_approval_remarks',
        'veh_recommend_status',
        'veh_emp_approval_pk',
        'created_by',
        'created_date',
        'modified_by',
        'modified_date',
    ];

    protected $casts = [
        'created_date' => 'datetime',
        'modified_date' => 'datetime',
    ];

    public function vehicleApplication()
    {
        return $this->belongsTo(VehiclePassDuplicateApplyTwfw::class, 'vehicle_TW_pk', 'vehicle_tw_pk');
    }

    public function approvedBy()
    {
        return $this->belongsTo(EmployeeMaster::class, 'created_by', 'pk');
    }
}
