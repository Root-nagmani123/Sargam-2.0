<?php

namespace App\Models\FC;
use App\Models\FC\Concerns\FcUserAware;
use Illuminate\Database\Eloquent\Model;

class StudentRegisterMaster extends Model {
    use FcUserAware;
    protected $table = 'student_register_masters';
    protected $fillable = ['user_id', 'username','session_id','allotted_hostel','room_no','joining_date','joined','joining_remarks'];
    protected $casts = ['joining_date'=>'date','joined'=>'boolean'];
    public function session() { return $this->belongsTo(SessionMaster::class,'session_id'); }
}
