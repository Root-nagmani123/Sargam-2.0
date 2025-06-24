<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HostelRoomMaster extends Model
{
    protected $table = 'hostel_room_master';
    protected $primaryKey = 'pk';
    
    protected $guarded = [];

    public $timestamps = false;
}
