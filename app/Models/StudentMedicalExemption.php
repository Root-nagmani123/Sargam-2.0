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

/**
 * Exemptions on a course that has not ended (the listing's Active tab).
 * Shared by the listing, its export and the Doctor dashboard card.
 */
public function scopeOnRunningCourse($query)
{
    return $query->whereHas('course', fn ($q) => $q->whereDate('end_date', '>=', now()->toDateString()));
}

/**
 * Exemptions whose period overlaps the whole days $from..$to (Y-m-d, either may be
 * null). No to_date means the exemption is still running.
 */
public function scopeOverlapping($query, ?string $from, ?string $to)
{
    return $query
        ->when($from, fn ($q) => $q->where(fn ($q) => $q->whereDate('to_date', '>=', $from)->orWhereNull('to_date')))
        ->when($to, fn ($q) => $q->whereDate('from_date', '<=', $to));
}
}
