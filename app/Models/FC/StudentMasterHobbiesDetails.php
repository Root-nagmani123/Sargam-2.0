<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;
use App\Models\FC\Concerns\FcUserAware;

class StudentMasterHobbiesDetails extends Model {
    use FcUserAware;
    protected $table = 'student_master_hobbies_details';
    protected $fillable = ['user_id', 'username','hobbies','special_skills','extra_curricular'];
}
