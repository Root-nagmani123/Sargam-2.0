<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HostelBuildingMaster extends Model
{
    protected $table = 'hostel_building_master';
    protected $primaryKey = 'pk';
    public $timestamps = false;

    public $guarded = [];

    public function scopeActive()
    {
        return $this->where('active_inactive', 1);
    }
}
