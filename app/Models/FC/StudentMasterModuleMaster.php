<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class StudentMasterModuleMaster extends Model {
    protected $table = 'student_master_module_masters';
    protected $fillable = ['username','chosen_module','second_module'];
}
