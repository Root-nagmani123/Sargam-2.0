<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehiclePassTWApply extends Model
{
    protected $table = 'vehicle_pass_tw_apply';
    protected $primaryKey = 'vehicle_tw_pk';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    /**
     * Fillable aligned with SQL: vehicle_pass_tw_apply (pk, vehicle_tw_pk, employee_id_card, vehicle_type, vehicle_no, ...).
     * No applicant_type, applicant_name, designation, department in table; display from employee relation.
     */
    protected $fillable = [
        'vehicle_tw_pk',
        'employee_id_card',
        'emp_master_pk',
        'vehicle_type',
        'vehicle_no',
        'vehicle_req_id',
        'doc_upload',
        'vehicle_card_reapply',
        'veh_card_valid_from',
        'vech_card_valid_to',
        'vech_card_status',
        'app_remarks',
        'created_date',
        'veh_card_forward',
        'veh_card_genrated_date',
        'veh_card_forward_status',
        'veh_created_by',
        'gov_veh',
        'applicant_type',
        'applicant_name',
        'designation',
        'department',
    ];

    protected $casts = [
        'veh_card_valid_from' => 'date',
        'vech_card_valid_to' => 'date',
        'created_date' => 'datetime',
        'veh_card_genrated_date' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(EmployeeMaster::class, 'emp_master_pk', 'pk');
    }

    public function vehicleType()
    {
        return $this->belongsTo(SecVehicleType::class, 'vehicle_type', 'pk');
    }

    public function createdBy()
    {
        return $this->belongsTo(EmployeeMaster::class, 'veh_created_by', 'pk');
    }

    public function approval()
    {
        return $this->hasOne(VehiclePassTWApplyApproval::class, 'vehicle_TW_pk', 'vehicle_tw_pk');
    }

    public function approvals()
    {
        return $this->hasMany(VehiclePassTWApplyApproval::class, 'vehicle_TW_pk', 'vehicle_tw_pk');
    }

    public function getStatusTextAttribute()
    {
        return match($this->vech_card_status) {
            1 => 'Pending',
            2 => 'Approved',
            3 => 'Rejected',
            default => 'Unknown'
        };
    }

    public function getForwardStatusTextAttribute()
    {
        return match($this->veh_card_forward_status) {
            0 => 'Not Forwarded',
            1 => 'Forwarded',
            2 => 'Card Ready',
            default => 'Unknown'
        };
    }

    /**
     * Display name: from employee relation (name + ID) or employee_id_card only (e.g. Others).
     */
    public function getDisplayNameAttribute()
    {
        if ($this->employee) {
            $name = trim($this->employee->first_name . ' ' . ($this->employee->last_name ?? ''));
            $id = $this->employee_id_card ?: ($this->employee->emp_id ?? '');
            return $id ? $name . ' (' . $id . ')' : $name;
        }
        return $this->employee_id_card ?: '--';
    }
}
