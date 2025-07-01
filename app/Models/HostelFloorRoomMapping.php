<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HostelFloorRoomMapping extends Model
{
    protected $table = 'hostel_floor_room_mapping';
    protected $guarded = [];
    public $timestamps = false;
    protected $primaryKey = "pk";

    public function scopeActive($query) {
        return $query->where('active_inactive', 1);
    }
    public function buildingFloor() {
        return $this->belongsTo(HostelBuildingFloorMapping::class, 'hostel_building_floor_mapping_pk', 'pk');
    }

    public function room() {
        return $this->belongsTo(HostelRoomMaster::class, 'hostel_room_master_pk', 'pk');
    }
}
