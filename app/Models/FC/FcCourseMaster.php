<?php

namespace App\Models\FC;

use Illuminate\Database\Eloquent\Model;

class FcCourseMaster extends Model
{
    protected $table = 'fc_course_master';

    protected $fillable = ['c_code', 'c_name', 'status'];

    public function scopeActive($q)
    {
        return $q->where('status', 1);
    }
}
