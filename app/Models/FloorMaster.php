<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FloorMaster extends Model
{
    protected $table = 'floor_master';
    protected $primaryKey = 'pk';
    protected $guarded = [];

    public $timestamps = false;

    function scopeActive($query)
    {
        return $query->where('active_inactive', 1);
    }
}
