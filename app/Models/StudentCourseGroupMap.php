<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentCourseGroupMap extends Model
{
    protected $table = "student_course_group_map";
    protected $primaryKey = "pk";
    protected $guarded = [];
    public $timestamps = false;

    public function studentsMaster()
    {
        return $this->hasOne(StudentMaster::class, 'pk', 'student_master_pk');
    }
    
    // public function courseGroups()
    // {
    //     return $this->belongsTo(CourseGroup::class, 'course_group_id', 'id');
    // }
}
