<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeMaster extends Model
{
    protected $table = 'employee_master';
    public $timestamps = false;

    protected $guarded = [];
    protected $primaryKey = 'pk';

    public const title = [
        'Mr' => 'Mr',
        'Mrs' => 'Mrs',
        'Ms' => 'Ms',
        'Dr' => 'Dr',
    ];

    public const gender = [
        'Male' => 'Male',
        'Female' => 'Female',
        'Other' => 'Other'
    ];

    public const maritalStatus = [
        'Married' => 'Married',
        'Unmarried' => 'Unmarried'
    ];

    /**
     * Get the full name attribute (first_name + last_name)
     */
    public function getNameAttribute()
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    
    public static function getDeputationEmployeeList()
    {
        $deputationEmployeeList = self::where('emp_type', EmployeeTypeMaster::getDeputationPK())->get();
        return $deputationEmployeeList;
    }

    public static function getFullName($pk)
    {
        $employee = self::find($pk);
        return $employee->first_name . ' ' . $employee->last_name;
    }

    public static function getDeputationEmployeeListNameAndPK() {
        $deputationEmployeeList = self::getDeputationEmployeeList();
        $deputationEmployeeList = $deputationEmployeeList->map(function ($item) {
            $item['name'] = $item->first_name . ' ' . $item->last_name;
            return $item;
        });
        $deputationEmployeeList = $deputationEmployeeList->toArray();
        $deputationEmployeeList = array_column($deputationEmployeeList, 'name', 'pk');

        return $deputationEmployeeList;
    }

    public function assignedRoles()
    {
        $userCredential = UserCredential::where('user_id', $this->pk)->first();
        if(!$userCredential) {
            return collect();
        }

        $userRoleMaster = EmployeeRoleMapping::where('user_credentials_pk',  $userCredential->pk)->get();
        if(!$userRoleMaster) {
            return collect();
        }
        
        $assignedRoles = [];
        // dd($userRoleMaster);
        $userRoleMaster->each(function ($role) use (&$assignedRoles) {
            UserRoleMaster::where('pk', $role->user_role_master_pk)
                ->get()
                ->each(function ($role) use (&$assignedRoles) {
                    $assignedRoles[] = [
                        'role_name' => $role->user_role_name,

                ];
            });
        });
        return collect($assignedRoles);
    }

    public function designation()
    {
        return $this->belongsTo(DesignationMaster::class, 'designation_master_pk', 'pk');
    }

    public function department()
    {
        return $this->belongsTo(DepartmentMaster::class, 'department_master_pk', 'pk');
    }

    public function employeeType()
    {
        return $this->belongsTo(EmployeeTypeMaster::class, 'emp_type', 'pk');
    }

    public function employeeGroup()
    {
        return $this->belongsTo(EmployeeGroupMaster::class, 'emp_group_pk', 'pk');
    }

    public function userCredential()
    {
        return $this->hasOne(UserCredential::class, 'user_id', 'pk')->where('user_category', 'E');
    }

    public function employeeRoleMapping()
    {
        $userCredential = UserCredential::where('user_id', $this->pk)->first();
        if(!$userCredential) {
            return collect();
        }

        return EmployeeRoleMapping::where('user_credentials_pk',  $userCredential->pk)->pluck('user_role_master_pk');
    }
}
