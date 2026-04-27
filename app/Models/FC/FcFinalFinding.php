<?php

namespace App\Models\FC;

use Illuminate\Database\Eloquent\Model;

class FcFinalFinding extends Model
{
    protected $table = 'fc_final_findings';

    protected $fillable = ['userid', 'findings', 'course', 'submited_by', 'status', 'submit_dt'];

    protected $casts = ['submit_dt' => 'datetime'];
}
