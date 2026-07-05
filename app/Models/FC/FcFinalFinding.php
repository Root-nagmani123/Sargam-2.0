<?php

namespace App\Models\FC;
use App\Models\FC\Concerns\FcUserAware;

use Illuminate\Database\Eloquent\Model;

class FcFinalFinding extends Model
{
    use FcUserAware;
    protected $table = 'fc_final_findings';

    protected $fillable = ['user_id', 'userid', 'findings', 'course', 'submited_by', 'status', 'submit_dt'];
    // 'userid' = pre-migration, 'user_id' = post-migration

    protected $casts = ['submit_dt' => 'datetime'];
}
