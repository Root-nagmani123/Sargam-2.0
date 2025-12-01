<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentMaster extends Model
{
    protected $table = "student_master";
    protected $primaryKey = 'pk';
    public $timestamps = false;
    protected $guarded = [];

    function courseStudentAttendance()
    {
        return $this->hasMany(CourseStudentAttendance::class, 'Student_master_pk', 'pk');
    }

    // student master course map listing

    public function courses()
    {
        return $this->belongsToMany(CourseMaster::class, 'student_master_course__map', 'student_master_pk', 'course_master_pk')
            ->withPivot('active_inactive', 'created_date', 'modified_date');
    }


    // Relation with service
    public function service()
    {
        return $this->belongsTo(
            ServiceMaster::class,
            'service_master_pk', // foreign key on student_master
            'pk'                 // primary key on service_master
        );
    }
}
