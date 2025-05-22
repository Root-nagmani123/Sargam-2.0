<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Timetable extends Model
{
    protected $table = 'timetable';
    protected $guarded = [];

    public $timestamps = false;
    protected $primaryKey = 'id';

    public function scopeActive($query)
    {
        return $query->where('active_inactive', 1);
    }

    public function scopeInactive($query)
    {
        return $query->where('active_inactive', 0);
    }

    public function courseGroupTypeMaster()
    {
        return $this->belongsTo(CourseMaster::class, 'course_group_type_master', 'pk');
    }

    public function classSession()
    {
        return $this->belongsTo(ClassSessionMaster::class, 'class_session_master_pk', 'pk');
    }

    public function venue()
    {
        return $this->belongsTo(VenueMaster::class, 'venue_id', 'venue_id');
    }

    public function faculty()
    {
        return $this->belongsTo(FacultyMaster::class, 'faculty_master', 'pk');
    }

}
