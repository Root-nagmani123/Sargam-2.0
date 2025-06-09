<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalendarEvent extends Model
{
    protected $table = 'timetable';


    protected $primaryKey = 'pk'; // yahan apni actual primary key lagayein

    public $timestamps = false;
    protected $fillable = [
        'course_master_pk',
        'subject_master_pk',
        'subject_module_master_pk',
        'subject_topic',
        'course_group_type_master',
        'group_name',
        'faculty_master',
        'faculty_type',
        'venue_id',
        'class_session',
        'START_DATE',
        'END_DATE',
        'feedback_checkbox',
        'ratting_checkbox',
        'remark_checkbox',
        'bio_attendance',
        'active_inactive',
        'created_date',
        'modified_date',
    ];

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