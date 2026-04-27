<?php

namespace App\Models\FC;

use Illuminate\Database\Eloquent\Model;

class FcPathReport extends Model
{
    protected $table = 'fc_path_report';

    protected $fillable = ['userid', 'path_report', 'doc_report', 'course', 'status', 'submit_dt'];

    protected $casts = ['submit_dt' => 'datetime'];
}
