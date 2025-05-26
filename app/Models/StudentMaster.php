<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentMaster extends Model
{
    protected $table = "student_master";

    function courseStudentAttendance()
    {
        return $this->hasMany(CourseStudentAttendance::class, 'Student_master_pk', 'pk');
    }
}
