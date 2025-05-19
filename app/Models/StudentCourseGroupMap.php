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

    public function student()
    {
        return $this->belongsTo(StudentMaster::class, 'student_master_pk', 'pk');
    }

    public function groupTypeMasterCourseMasterMap()
    {
        return $this->belongsTo(GroupTypeMasterCourseMasterMap::class, 'group_type_master_course_master_map_pk', 'pk');
    }

}
