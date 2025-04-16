<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class DepartmentMaster extends Model
{
    protected $table = 'department_master';
    protected $primaryKey = 'pk';
    public $timestamps = false;

    protected $guarded = [];

    // Cache the department list
    public static function getDepartmentList()
    {
        $departmentList = self::select('pk', 'department_name')->get();
        return $departmentList->toArray();
    }
}
