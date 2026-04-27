<?php

namespace App\Models\FC;

use Illuminate\Database\Eloquent\Model;

class FcOtDetail extends Model
{
    protected $table = 'fc_ot_details';

    protected $fillable = [
        'username',
        'otname',
        'otcode',
        'course',
        'c_name',
        'gender',
        'dob',
        'age',
        'father_name',
        'mobileno',
        'blood_group',
        'aadhar_no',
        'abha_id',
        'house',
        'housen',
        'service',
        'status',
    ];

    public function activities()
    {
        return $this->hasMany(FcOtActivity::class, 'username', 'username');
    }

    public function preHistory()
    {
        return $this->hasMany(FcPreHistory::class, 'userid', 'username');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeByCourse($query, string $course)
    {
        return $query->where('course', $course);
    }

    public function hasPreHistory(?string $course = null): bool
    {
        $q = $this->preHistory();
        if ($course) {
            $q->where('course', $course);
        }
        return $q->exists();
    }
}
