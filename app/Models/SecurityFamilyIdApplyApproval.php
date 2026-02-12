<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Maps to security table: security_family_id_apply_approval
 */
class SecurityFamilyIdApplyApproval extends Model
{
    protected $table = 'security_family_id_apply_approval';

    public $timestamps = false;

    protected $fillable = [
        'security_fm_id_apply_pk',
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

    public function securityFamilyIdApply()
    {
        return $this->belongsTo(SecurityFamilyIdApply::class, 'security_fm_id_apply_pk', 'fml_id_apply');
    }
}
