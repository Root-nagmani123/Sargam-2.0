<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;
use App\Models\FC\Concerns\FcUserAware;

class StudentSkillDetailsMaster extends Model {
    use FcUserAware;
    protected $table = 'student_skill_details_masters';
    protected $fillable = ['user_id', 'username','skill_name','skill_level','year_acquired'];
}
