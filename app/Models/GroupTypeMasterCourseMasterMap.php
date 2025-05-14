<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupTypeMasterCourseMasterMap extends Model
{
    protected $table = "group_type_master_course_master_map";
    protected $primaryKey = "pk";
    protected $guarded = [];
    public $timestamps = false;


    // public function courses()
    // {
    //     return $this->hasMany(CourseMaster::class, 'pk', 'course_name');
    // }

    public function studentCourseGroupMap()
    {
        return $this->hasMany(StudentCourseGroupMap::class, 'group_type_master_course_master_map_pk', 'pk');
    }

    public function courseGroupType()
    {
        return $this->belongsTo(CourseGroupTypeMaster::class, 'type_name', 'pk');
    }

}
