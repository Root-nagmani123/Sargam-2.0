<?php

namespace App\Models\FC;
use App\Models\FC\Concerns\FcUserAware;

use Illuminate\Database\Eloquent\Model;

class FcPathReport extends Model
{
    use FcUserAware;
    protected $table = 'fc_path_report';

    protected $fillable = ['user_id', 'userid', 'path_report', 'doc_report', 'course', 'status', 'submit_dt'];
    // 'user_id' = post-migration, 'userid' = pre-migration column name

    protected $casts = ['submit_dt' => 'datetime'];
}
