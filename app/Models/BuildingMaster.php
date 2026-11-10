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

    /** COE sees only buildings of this type (examination classrooms). */
    public const TYPE_CLASSROOM = 'Classroom';

    public static $buildingType = [
        'Administration' => 'Administration',
        'Hostel' => 'Hostel',
        'Resident' => 'Resident',
        'Guest' => 'Guest',
        'Classroom' => 'Classroom',
    ];

    function scopeClassroom($query)
    {
        return $query->where('building_type', self::TYPE_CLASSROOM);
    }

    function scopeActive($query)
    {
        return $query->where('active_inactive', 1);
    }
}
