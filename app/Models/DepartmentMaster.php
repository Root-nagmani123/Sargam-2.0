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

    public function scopeActive($query)
    {
        return $query->where('active_inactive', 1);
    }
    public static function getDepartmentList()
    {
        $departmentList = self::active()->select('pk', 'department_name')->get();
        return $departmentList->toArray();
    }
}
