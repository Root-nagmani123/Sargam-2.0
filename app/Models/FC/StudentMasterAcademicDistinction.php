<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;
use App\Models\FC\Concerns\FcUserAware;

class StudentMasterAcademicDistinction extends Model {
    use FcUserAware;
    protected $table = 'student_master_academic_distinctions';
    protected $fillable = ['user_id', 'username','distinction_type','description','year','awarding_body'];
}
