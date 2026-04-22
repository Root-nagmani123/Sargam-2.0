<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class FcJoiningAttendanceGangaMaster extends Model {
    protected $table = 'fc_joining_attendance_ganga_masters';
    protected $fillable = ['username','room_no','joining_date','joining_time','transport_mode','attended','remarks'];
    protected $casts = ['joining_date'=>'date','attended'=>'boolean'];
}
