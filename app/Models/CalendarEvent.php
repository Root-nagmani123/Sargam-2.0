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
    'class_session_master_pk',
    'fullday',
    'mannual_starttime',
    'mannual_end_time',
    'feedback_checkbox',
    'ratting_checkbox',
    'remark_checkbox',
    'bio_attendance',
    'active_inactive',
    'created_date',
    'modified_date',
];
}
