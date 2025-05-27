<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MDOEscotDutyMap extends Model
{
    protected $table = "mdo_escot_duty_map";
    protected $guarded = [];

    public $timestamps = false;

    protected $primaryKey = 'pk';

    public function courseMaster()
    {
        return $this->belongsTo(CourseMaster::class, 'course_master_pk', 'pk');
    }
    public function mdoDutyTypeMaster()
    {
        return $this->belongsTo(MDODutyTypeMaster::class, 'mdo_duty_type_master_pk', 'pk');
    }

    public function studentMaster()
    {
        return $this->belongsTo(StudentMaster::class, 'selected_student_list', 'pk');
    }
}
