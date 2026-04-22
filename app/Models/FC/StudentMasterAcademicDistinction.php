<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class StudentMasterAcademicDistinction extends Model {
    protected $table = 'student_master_academic_distinctions';
    protected $fillable = ['username','distinction_type','description','year','awarding_body'];
}
