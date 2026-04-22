<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class StudentMasterIncompletMaster extends Model {
    protected $table = 'student_master_incomplet_masters';
    protected $fillable = ['username','incomplete_reason','incomplete_fields'];
}
