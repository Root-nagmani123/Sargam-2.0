<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class StudentMasterEmploymentDetails extends Model {
    protected $table = 'student_master_employment_details';
    protected $fillable = ['username','organisation_name','designation','job_type_id','from_date','to_date','is_current'];
    protected $casts = ['from_date'=>'date','to_date'=>'date','is_current'=>'boolean'];
    public function jobType() { return $this->belongsTo(JobTypeMaster::class,'job_type_id'); }
}
