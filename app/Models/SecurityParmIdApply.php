<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Maps to security table: security_parm_id_apply
 * Employee (Permanent) ID Card applications.
 */
class SecurityParmIdApply extends Model
{
    protected $table = 'security_parm_id_apply';

    protected $primaryKey = 'emp_id_apply';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'emp_id_apply',
        'employee_master_pk',
        'sec_id_card_config_pk',
        'designation_pk',
        'card_valid_from',
        'card_valid_to',
        'id_card_no',
        'id_status',
        'remarks',
        'created_by',
        'created_date',
        'id_card_forward',
        'id_card_generate_date',
        'id_card_forward_status',
        'id_card_reapply',
        'id_photo_path',
        'joining_letter_path',
        'employee_dob',
        'mobile_no',
        'blood_group',
        'card_type',
        'permanent_type',
        'perm_sub_type',
        'telephone_no',
        'path_image',
        'extension_reason',
        'extension_document_path',
    ];

    protected $casts = [
        'card_valid_from' => 'date',
        'card_valid_to' => 'date',
        'employee_dob' => 'date',
        'created_date' => 'datetime',
        'id_card_generate_date' => 'datetime',
    ];

    /** id_status: 1=Pending, 2=Approved, 3=Rejected */
    const ID_STATUS_PENDING = 1;
    const ID_STATUS_APPROVED = 2;
    const ID_STATUS_REJECTED = 3;

    public function employee()
    {
        return $this->belongsTo(EmployeeMaster::class, 'employee_master_pk', 'pk');
    }

    public function approvals()
    {
        return $this->hasMany(SecurityParmIdApplyApproval::class, 'security_parm_id_apply_pk', 'emp_id_apply');
    }

    /** Person who submitted the request (created_by = employee_master_pk). */
    public function creator()
    {
        return $this->belongsTo(EmployeeMaster::class, 'created_by', 'pk');
    }

    /** Approval I = status 1, Approval II = status 2, Rejected = status 3 */
    public function approval1Row()
    {
        return $this->hasOne(SecurityParmIdApplyApproval::class, 'security_parm_id_apply_pk', 'emp_id_apply')->where('status', 1);
    }

    public function approval2Row()
    {
        return $this->hasOne(SecurityParmIdApplyApproval::class, 'security_parm_id_apply_pk', 'emp_id_apply')->where('status', 2);
    }

    public function getStatusLabelAttribute()
    {
        return match ((int) $this->id_status) {
            self::ID_STATUS_PENDING => 'Pending',
            self::ID_STATUS_APPROVED => 'Approved',
            self::ID_STATUS_REJECTED => 'Rejected',
            default => 'Unknown',
        };
    }
}
