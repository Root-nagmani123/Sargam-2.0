<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HostelBuildingMaster extends Model
{
    protected $table = 'hostel_building_master';
    protected $primaryKey = 'pk';
    public $timestamps = false;

    public $guarded = [];

    /**
     * hostel_building_master stores its status in `active_room`; it has no
     * `active_inactive` column, so the previous scope threw "Unknown column"
     * on every caller. Column list confirmed against the schema, and it is the
     * same column the status switch and the toggle allow-list use.
     */
    public function scopeActive($query)
    {
        return $query->where('active_room', 1);
    }
}
