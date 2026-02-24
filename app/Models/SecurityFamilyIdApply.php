<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Maps to security table: security_family_id_apply
 * Family ID Card applications.
 */
class SecurityFamilyIdApply extends Model
{
    protected $table = 'security_family_id_apply';

    protected $primaryKey = 'fml_id_apply';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'fml_id_apply',
        'family_name',
        'sec_id_card_config_pk',
        'family_relation',
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
        'employee_dob',
        'mobile_no',
        'blood_group',
        'telephone_no',
        'vender_name',
        'emp_id_apply',
        'employee_type',
        'department_approval_emp_pk',
        'family_photo',
        'dup_pk',
        'dup_reason',
        'dup_doc',
    ];

    protected $casts = [
        'card_valid_from' => 'date',
        'card_valid_to' => 'date',
        'employee_dob' => 'date',
        'created_date' => 'datetime',
        'id_card_generate_date' => 'datetime',
    ];

    public function approvals()
    {
        return $this->hasMany(SecurityFamilyIdApplyApproval::class, 'security_fm_id_apply_pk', 'fml_id_apply');
    }
}
