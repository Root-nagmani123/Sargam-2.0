<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class StudentMasterSpouseMaster extends Model {
    protected $table = 'student_master_spouse_masters';
    protected $fillable = ['username','spouse_name','spouse_dob','spouse_occupation','spouse_organisation',
        'no_of_children','children_details'];
    protected $casts = ['spouse_dob'=>'date'];
}
