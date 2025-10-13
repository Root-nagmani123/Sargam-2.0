<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuildingMaster extends Model
{
    protected $table = 'building_master';
    protected $primaryKey = 'pk';
    protected $guarded = [];

    public $timestamps = false;

    public static $buildingType = [
        'Administration' => 'Administration', 
        'Hostel' => 'Hostel', 
        'Resident' => 'Resident', 
        'Guest' => 'Guest'
    ];

    function scopeActive($query)
    {
        return $query->where('active_inactive', 1);
    }
}
