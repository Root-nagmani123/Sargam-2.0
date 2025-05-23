<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseGroupTimetableMapping extends Model
{
    protected $table = 'course_group_timetable_mapping';

    protected $fillable = [
        'group_pk',
        'course_group_type_master',
        'Programme_pk',
        'timetable_pk',
    ];

    public $timestamps = false; // if you don't have created_at/updated_at
}

