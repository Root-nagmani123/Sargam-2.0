<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuildingFloorRoomMapping extends Model
{
    protected $table = 'building_floor_room_mapping';
    protected $primaryKey = 'pk';
    protected $guarded = [];
    public $timestamps = false;

    public static $roomTypes = [
        'Room' => 'Room',
        'Pantry' => 'Pantry',
        'Reception' => 'Reception',
        'Lounge' => 'Lounge',
        'Boiler room' => 'Boiler room',
        'Inner Loundry' => 'Inner Loundry',
        'Attendant room' => 'Attendant room',
        'Outer Loundary' => 'Outer Loundary',
        'Cafe' => 'Cafe',
        'BVG clearness Room' => 'BVG clearness Room',
        'Laundry' => 'Laundry',
        'Dining Hall' => 'Dining Hall',
        'Manager Room' => 'Manager Room',
        'Store Room' => 'Store Room',
        'Open Place' => 'Open Place',
        'Launge' => 'Launge',
        'Mess Area' => 'Mess Area',
        'Business centre' => 'Business centre',
        'Store II' => 'Store II',
        'Cafeteria' => 'Cafeteria',
        'Pantery' => 'Pantery',
        'Store Upside' => 'Store Upside',
        'Staff Room' => 'Staff Room',
        'Gym' => 'Gym'
    ];
    function building()
    {
        return $this->belongsTo(BuildingMaster::class, 'building_master_pk', 'pk');
    }

    function floor()
    {
        return $this->belongsTo(FloorMaster::class, 'floor_master_pk', 'pk');
    }
}
