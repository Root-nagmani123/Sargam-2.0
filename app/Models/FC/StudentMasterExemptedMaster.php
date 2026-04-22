<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class StudentMasterExemptedMaster extends Model {
    protected $table = 'student_master_exempted_masters';
    protected $fillable = ['username','is_exempted','exemption_reason','doc_path'];
    protected $casts = ['is_exempted'=>'boolean'];
}
