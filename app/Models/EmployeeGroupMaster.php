<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeGroupMaster extends Model
{
    protected $table = 'employee_group_master';
    protected $primaryKey = 'pk';
    public $timestamps = false;

    protected $guarded = [];

    public static function getEmployeeGroupList()
    {
        $employeeGroupList = self::select('pk', 'group_name')->get();
        return $employeeGroupList->toArray();
    }
    
    
}
