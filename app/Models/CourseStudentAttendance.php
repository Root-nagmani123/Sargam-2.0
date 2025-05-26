<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseStudentAttendance extends Model
{
    protected $table = "course_student_attendance";
    protected $primaryKey = "pk";
    protected $guarded = [];
    public $timestamps = false;

    public function student()
    {
        return $this->belongsTo(StudentMaster::class, 'Student_master_pk', 'pk');
    }

    public function course()
    {
        return $this->belongsTo(CourseMaster::class, 'course_pk', 'pk');
    }

    
}
