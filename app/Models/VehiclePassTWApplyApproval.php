<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehiclePassTWApplyApproval extends Model
{
    protected $table = 'vehicle_pass_tw_apply_approval';
    protected $primaryKey = 'pk';
    public $timestamps = false;

    /** Aligned with SQL: vehicle_pass_tw_apply_approval (vehicle_TW_pk varchar, created_by, veh_emp_approval_pk, modified_by, etc.) */
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
        return $this->belongsTo(VehiclePassTWApply::class, 'vehicle_TW_pk', 'vehicle_tw_pk');
    }

    public function approvedBy()
    {
        return $this->belongsTo(EmployeeMaster::class, 'created_by', 'pk');
    }

    public function getRecommendStatusTextAttribute()
    {
        return match($this->veh_recommend_status) {
            1 => 'Recommended',
            2 => 'Approved',
            3 => 'Rejected',
            default => 'Pending'
        };
    }

    public function getStatusTextAttribute()
    {
        return match($this->status) {
            0 => 'Pending',
            2 => 'Approved',
            3 => 'Rejected',
            default => 'Unknown'
        };
    }
}
