<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OTHostelRoomDetails extends Model
{
    protected $table = 'ot_hostel_room_details';
    protected $primaryKey = 'pk';
    public $timestamps = false;
    protected $guarded = [];

    // function user()
    // {
    //     return $this->belongsTo(User::class, 'user_pk', 'pk');
    // }

    function course()
    {
        return $this->belongsTo(CourseMaster::class, 'course_master_pk', 'pk');
    }
}
