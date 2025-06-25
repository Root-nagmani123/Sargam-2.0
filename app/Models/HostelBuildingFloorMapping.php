<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HostelBuildingFloorMapping extends Model
{
    protected $table = 'hostel_building_floor_mapping';
    protected $guarded = [];
    public $timestamps = false;
    protected $primaryKey = "pk";

    public function building() {
        return $this->belongsTo(HostelBuildingMaster::class, 'hostel_building_master_pk', 'pk');
    }

    public function floor() {
        return $this->belongsTo(HostelFloorMaster::class, 'hostel_floor_master_pk', 'pk');
    }
}
