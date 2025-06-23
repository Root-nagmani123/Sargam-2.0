<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeRoleMapping extends Model
{
    protected $table = 'employee_role_mapping';

    protected $primaryKey = 'pk';
    protected $guarded = [];
    public $timestamps = false;
    // public function employee()
    // {
    //     return $this->belongsTo(EmployeeMaster::class, 'employee_master_pk', 'pk');
    // }

    // public function role()
    // {
    //     return $this->belongsTo(Role::class, 'role_id', 'id');
    // }
}
