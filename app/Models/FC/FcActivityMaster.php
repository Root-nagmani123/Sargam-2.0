<?php

namespace App\Models\FC;

use Illuminate\Database\Eloquent\Model;

class FcActivityMaster extends Model
{
    protected $table = 'fc_activity_master';

    protected $fillable = ['menuid', 'menun', 'ccode', 'status'];

    public function scopeActive($q)
    {
        return $q->where('status', 1);
    }

    public function scopeForCourse($q, string $ccode)
    {
        return $q->where('ccode', $ccode);
    }
}
