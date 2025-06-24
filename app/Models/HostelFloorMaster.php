<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HostelFloorMaster extends Model
{
    protected $table = 'hostel_floor_master';
    protected $primaryKey = 'pk';

    protected $gurded = [];
    
    public $timestamps = false;
}
