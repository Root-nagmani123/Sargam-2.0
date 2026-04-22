<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class FcJoiningAttendanceNarmadaMaster extends Model {
    protected $table = 'fc_joining_attendance_narmada_masters';
    protected $fillable = ['username','room_no','joining_date','joining_time','attended','remarks'];
    protected $casts = ['joining_date'=>'date','attended'=>'boolean'];
}
