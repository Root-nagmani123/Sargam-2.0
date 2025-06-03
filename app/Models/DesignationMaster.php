<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DesignationMaster extends Model
{
    protected $table = 'designation_master';
    protected $primaryKey = 'pk';
    public $timestamps = false;

    protected $guarded = [];

    public function scopeActive($query)
    {
        return $query->where('active_inactive', 1);
    }
    public static function getDesignationList()
    {
        $designationList = self::active()->select('pk', 'designation_name')->get();
        return $designationList->toArray();
    }
}
