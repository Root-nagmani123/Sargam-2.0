<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class StudentExemptionMedDocMaster extends Model {
    protected $table = 'student_exemption_med_doc_masters';
    protected $fillable = ['user_id','ailment','doc_path','exemption_granted','granted_by'];
    protected $casts = ['exemption_granted'=>'boolean'];
}
