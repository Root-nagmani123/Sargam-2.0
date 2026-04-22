<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class StudentRegisterReport extends Model {
    protected $table = 'student_register_masters';
    protected $fillable = ['username','session_id','allotted_hostel','room_no','joining_date','joined'];
}
