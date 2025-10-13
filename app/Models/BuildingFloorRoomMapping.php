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

    function building()
    {
        return $this->belongsTo(BuildingMaster::class, 'building_master_pk', 'pk');
    }

    function floor()
    {
        return $this->belongsTo(FloorMaster::class, 'floor_master_pk', 'pk');
    }
}
