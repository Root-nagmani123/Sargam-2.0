<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class StudentMasterHigherEducationalDetails extends Model {
    protected $table = 'student_master_higher_educational_details';
    protected $fillable = ['username','degree_type','subject_name','university_name','year_of_passing','percentage_cgpa'];
}
