<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class SubjectMaster extends Model {
    protected $table = 'subject_masters';
    protected $fillable = ['subject_name'];
}
