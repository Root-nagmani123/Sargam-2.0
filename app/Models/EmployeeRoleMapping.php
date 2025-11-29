<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeRoleMapping extends Model
{
    protected $table = 'employee_role_mapping';

    protected $primaryKey = 'pk';
    protected $guarded = [];
    public $timestamps = false;
    public function employee()
    {
        return $this->belongsTo(EmployeeMaster::class, 'user_credentials_pk', 'pk');
    }

    public function role()
    {
        return $this->belongsTo(UserRoleMaster::class, 'user_role_master_pk', 'pk');
    }
}
