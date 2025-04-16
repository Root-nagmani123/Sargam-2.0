<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeTypeMaster extends Model
{
    protected $table = 'employee_type_master';
    protected $primaryKey = 'pk';
    public $timestamps = false;

    public static function getEmployeeTypeList()
    {
        $employeeTypeList = self::select('pk', 'category_type_name')->get();
        return $employeeTypeList->toArray();
    }
}