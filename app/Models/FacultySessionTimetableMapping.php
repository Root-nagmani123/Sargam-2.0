<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacultySessionTimetableMapping extends Model
{
    protected $table = "faculty_session_timetable_mapping";
    protected $guarded = [];
    public $timestamps = false;
    public $primaryKey = "pk";
}
