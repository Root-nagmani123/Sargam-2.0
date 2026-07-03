<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class JobTypeMaster extends Model {
    protected $table = 'job_type_masters';
    protected $fillable = ['job_type_name'];
}
