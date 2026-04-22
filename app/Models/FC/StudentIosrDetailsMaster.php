<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class StudentIosrDetailsMaster extends Model {
    protected $table = 'student_iosr_details_masters';
    protected $fillable = ['username','iosr_roll_no','iosr_rank','interview_marks',
        'written_marks','total_marks','home_state'];
}
