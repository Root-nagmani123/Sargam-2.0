<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExaminationDriveComponentMap extends Model
{
    protected $table = 'examination_drive_component_maps';
    protected $guarded = [];

    public $timestamps = false;

    const SOURCES = ['Faculty', 'System', 'Manual Entry'];

    public function drive()
    {
        return $this->belongsTo(ExaminationDrive::class, 'examination_drive_id', 'id');
    }

    public function subject()
    {
        return $this->belongsTo(SubjectMaster::class, 'subject_master_pk', 'pk');
    }

    public function component()
    {
        return $this->belongsTo(ComponentMaster::class, 'component_master_pk', 'pk');
    }
}
