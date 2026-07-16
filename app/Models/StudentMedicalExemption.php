<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentMedicalExemption extends Model
{
    protected $table = 'student_medical_exemption';
    protected $primaryKey = 'pk';
    public $timestamps = false;

    protected $fillable = [
        'course_master_pk',
        'student_master_pk',
        'employee_master_pk',
        'created_by',
        'exemption_category_master_pk',
        'from_date',
        'to_date',
        'opd_category',
        'pt_outdoor_advise',
        'days',
        'exemption_medical_speciality_pk',
        'Description',
        'Doc_upload',
        'active_inactive',
        'created_date',
        'modified_date',
    ];

    protected $attributes = [
        'active_inactive' => 1,
    ];
    public function category()
{
    return $this->belongsTo(ExemptionCategoryMaster::class, 'exemption_category_master_pk', 'pk');
}

public function speciality()
{
    return $this->belongsTo(ExemptionMedicalSpecialityMaster::class, 'exemption_medical_speciality_pk', 'pk');
}

public function course()
{
    return $this->belongsTo(CourseMaster::class, 'course_master_pk', 'pk');
}
public function student()
{
    return $this->belongsTo(StudentMaster::class, 'student_master_pk', 'pk');
}

public function employee()
{
    return $this->belongsTo(EmployeeMaster::class, 'employee_master_pk', 'pk');
}

public function creator()
{
    return $this->belongsTo(\App\Models\User::class, 'created_by');
}
}
