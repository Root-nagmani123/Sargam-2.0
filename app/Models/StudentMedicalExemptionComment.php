<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentMedicalExemptionComment extends Model
{
    protected $table = 'student_medical_exemption_comments';
    protected $primaryKey = 'pk';
    public $timestamps = false;

    protected $fillable = [
        'student_medical_exemption_pk',
        'comment',
        'comment_date',
        'created_by',
        'created_date',
    ];

    public function exemption()
    {
        return $this->belongsTo(StudentMedicalExemption::class, 'student_medical_exemption_pk', 'pk');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
