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
        1 => 'Mr',
        2 => 'Mrs'
    ];

    public const gender = [
        1 => 'Male',
        2 => 'Female',
        3 => 'Other'
    ];

    public const maritalStatus = [
        1 => 'Single',
        2 => 'Married',
        3 => 'Other'
    ];

    public const casteCategory = [
        1 => 'General',
        2 => 'OBC',
        3 => 'SC',
        4 => 'ST',
        5 => 'Other'
    ];
    
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
}
