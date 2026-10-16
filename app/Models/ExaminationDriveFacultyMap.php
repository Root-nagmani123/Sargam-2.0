<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One faculty/subject coordination assignment for an examination drive. */
class ExaminationDriveFacultyMap extends Model
{
    protected $table = 'examination_drive_faculty_maps';

    protected $guarded = [];

    public $timestamps = false;

    public function drive(): BelongsTo
    {
        return $this->belongsTo(ExaminationDrive::class, 'examination_drive_id');
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(FacultyMaster::class, 'faculty_master_pk', 'pk');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(SubjectMaster::class, 'subject_master_pk', 'pk');
    }
}
