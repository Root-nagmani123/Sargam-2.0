<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;
use App\Models\FC\Concerns\FcUserAware;

class StudentMasterHigherEducationalDetails extends Model {
    use FcUserAware;
    protected $table = 'student_master_higher_educational_details';
    protected $fillable = ['user_id', 'username','degree_type','subject_name','university_name','year_of_passing','percentage_cgpa'];
}
