<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class StudentMaster extends Model {
    protected $table = 'student_masters';
    protected $fillable = ['username','session_id','roll_no','full_name','service_code','cadre',
        'status','step1_done','step2_done','step3_done','bank_done','travel_done','docs_done'];
    protected $casts = ['step1_done'=>'boolean','step2_done'=>'boolean','step3_done'=>'boolean',
        'bank_done'=>'boolean','travel_done'=>'boolean','docs_done'=>'boolean'];
    public function session() { return $this->belongsTo(SessionMaster::class,'session_id'); }
}

