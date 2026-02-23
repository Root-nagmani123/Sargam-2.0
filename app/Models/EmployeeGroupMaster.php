<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeGroupMaster extends Model
{
    protected $table = 'employee_group_master';
    protected $primaryKey = 'pk';
    public $timestamps = false;
    protected $fillable = ['emp_group_name', 'modified_date'];

    protected $guarded = [];

    public function scopeActive($query)
    {
        return $query->where('active_inactive', 1);
    }

    public static function getEmployeeGroupList()
    {
        $employeeGroupList = self::active()->select('pk', 'emp_group_name')->get();
        return $employeeGroupList->toArray();
    }


}
