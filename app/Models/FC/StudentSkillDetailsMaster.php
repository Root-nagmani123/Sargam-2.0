<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class StudentSkillDetailsMaster extends Model {
    protected $table = 'student_skill_details_masters';
    protected $fillable = ['username','skill_name','skill_level','year_acquired'];
}
