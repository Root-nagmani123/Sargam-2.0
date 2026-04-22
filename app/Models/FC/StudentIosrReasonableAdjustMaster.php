<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class StudentIosrReasonableAdjustMaster extends Model {
    protected $table = 'student_iosr_reasonable_adjust_masters';
    protected $fillable = ['username','adjustment_required','adjustment_type','doc_path'];
    protected $casts = ['adjustment_required'=>'boolean'];
}

