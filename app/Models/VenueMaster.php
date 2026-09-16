<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VenueMaster extends Model
{
    protected $table = 'venue_master';
    protected $primaryKey = 'venue_id';
    public $timestamps = false;

    protected $fillable = [
        'venue_name',
        'description',
        'venue_short_name',
        'building_master_pk',
        'floor_master_pk',
        'room_number',
        'capacity',
        'laptop_capacity',
        'seating_capacity',
        'created_date',
        'modified_date',
        'active_inactive',
    ];

    public function building()
    {
        return $this->belongsTo(BuildingMaster::class, 'building_master_pk', 'pk');
    }

    public function floor()
    {
        return $this->belongsTo(FloorMaster::class, 'floor_master_pk', 'pk');
    }
}
