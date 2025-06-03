<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeTypeMaster extends Model
{
    protected $table = 'employee_type_master';
    protected $primaryKey = 'pk';
    public $timestamps = false;
    public $created_at = 'created_date';
    public $updated_at = 'modified_date';

    public function scopeActive($query)
    {
        return $query->where('active_inactive', 1);
    }

    // get employee type list
    public static function getEmployeeTypeList()
    {
        $employeeTypeList = self::active()->select('pk', 'category_type_name')->get();
        return $employeeTypeList->toArray();
    }

    // get deputation pk
    public static function getDeputationPK()
    {
        $deputationPK = self::where(strtolower('category_type_name'), 'deputation')->first()->pk;
        return $deputationPK;
    }


}