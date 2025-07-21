<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentMasterCourseMap extends Model
{
    protected $table = 'student_master_course__map';
    protected $guarded = [];
    protected $primaryKey = 'pk';

    public function studentMaster()
    {
        return $this->belongsTo(StudentMaster::class, 'student_master_pk', 'pk');
    }
}
