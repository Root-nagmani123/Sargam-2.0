<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Maps to security table: IDCard_request_approvar_master_new
 * Approver master for ID card requests (Approval I / Approval II).
 * type: 'employee', sequence: 1 = Approval I, 2 = Approval II
 */
class IDCardRequestApprovarMasterNew extends Model
{
    protected $table = 'IDCard_request_approvar_master_new';

    protected $primaryKey = 'pk';

    public $timestamps = false;

    protected $fillable = [
        'employee_master_pk',
        'student_master_pk',
        'employees_pk',
        'type',
        'sequence',
        'is_forwarded',
        'cont_status',
        'per_status',
        'family_status',
        'traning_status',
        'duplicate_status',
        'vec_status',
    ];

    public function employee()
    {
        return $this->belongsTo(EmployeeMaster::class, 'employee_master_pk', 'pk');
    }
}
