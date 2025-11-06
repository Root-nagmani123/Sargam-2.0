<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CounsellorGroup extends Model
{
    protected $table = "counsellor_group";
    protected $primaryKey = "pk";
    protected $guarded = [];
    public $timestamps = false;

    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'updated_date';

    public function course()
    {
        return $this->belongsTo(CourseMaster::class, 'course_master_pk', 'pk');
    }

    public function faculty()
    {
        return $this->belongsTo(FacultyMaster::class, 'faculty_master_pk', 'pk');
    }
}

