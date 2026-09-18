<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExaminationDriveFacultyMap extends Model
{
    protected $table = 'examination_drive_faculty_maps';
    protected $guarded = [];

    public $timestamps = false;

    public function drive()
    {
        return $this->belongsTo(ExaminationDrive::class, 'examination_drive_id', 'id');
    }

    public function subject()
    {
        return $this->belongsTo(SubjectMaster::class, 'subject_master_pk', 'pk');
    }

    public function faculty()
    {
        return $this->belongsTo(FacultyMaster::class, 'faculty_master_pk', 'pk');
    }
}
