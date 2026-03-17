<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\EmployeeMaster;

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
        // Use veh_emp_approval_pk (actual approver) instead of created_by
        return $this->belongsTo(EmployeeMaster::class, 'veh_emp_approval_pk', 'pk');
    }

    /**
     * Accessor: resolve approver name using pk OR pk_old.
     */
    public function getApprovedByNameAttribute(): ?string
    {
        $id = $this->veh_emp_approval_pk;
        if (empty($id)) {
            return null;
        }

        $emp = EmployeeMaster::findByIdOrPkOld($id);
        if (! $emp) {
            return null;
        }

        // Prefer explicit emp_name column if present, else use first_name/last_name or name accessor.
        if (isset($emp->emp_name) && !empty($emp->emp_name)) {
            return $emp->emp_name;
        }

        if (method_exists($emp, 'getNameAttribute')) {
            $name = $emp->name;
            if (!empty($name)) {
                return $name;
            }
        }

        $first = (string) ($emp->first_name ?? '');
        $last = (string) ($emp->last_name ?? '');
        $full = trim($first . ' ' . $last);

        return $full !== '' ? $full : null;
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
