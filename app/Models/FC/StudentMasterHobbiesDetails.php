<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class StudentMasterHobbiesDetails extends Model {
    protected $table = 'student_master_hobbies_details';
    protected $fillable = ['username','hobbies','special_skills','extra_curricular'];
}
