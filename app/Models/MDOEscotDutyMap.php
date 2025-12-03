<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MDOEscotDutyMap extends Model
{
    protected $table = "mdo_escot_duty_map";
    protected $guarded = [];

    public $timestamps = false;

    protected $primaryKey = 'pk';

    public static function getMdoDutyTypes(): array
    {
        return [
            'mdo' => optional(MDODutyTypeMaster::whereRaw('LOWER(mdo_duty_type_name) = ?', ['mdo'])->first())->pk,
            'escort' => optional(MDODutyTypeMaster::whereRaw('LOWER(mdo_duty_type_name) = ?', ['escort'])->first())->pk,
            'other' => optional(MDODutyTypeMaster::whereRaw('LOWER(mdo_duty_type_name) = ?', ['other'])->first())->pk,
        ];
    }

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

    public function studentMasterCourseMap()
    {
        return $this->hasMany(StudentMasterCourseMap::class, 'course_master_pk', 'course_master_pk');
    }

    public function facultyMaster()
    {
        return $this->belongsTo(FacultyMaster::class, 'faculty_master_pk', 'pk');
    }
}
