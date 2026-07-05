<?php

namespace App\Models\FC;
use App\Models\FC\Concerns\FcUserAware;
use Illuminate\Database\Eloquent\Model;

class FcJoiningAttendanceMahanadiMaster extends Model {
    use FcUserAware;
    protected $table = 'fc_joining_attendance_mahanadi_masters';
    protected $fillable = ['user_id', 'username','room_no','joining_date','joining_time','attended','remarks'];
    protected $casts = ['joining_date'=>'date','attended'=>'boolean'];
}
