<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class StudentFcScaleMaster extends Model {
    protected $table = 'student_fc_scale_masters';
    protected $fillable = ['username','pay_level','basic_pay','grade_pay'];
}
