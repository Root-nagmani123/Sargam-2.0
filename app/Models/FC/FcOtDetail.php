<?php

namespace App\Models\FC;
use App\Models\FC\Concerns\FcUserAware;

use Illuminate\Database\Eloquent\Model;

class FcOtDetail extends Model
{
    use FcUserAware;
    protected $table = 'fc_ot_details';

    protected $fillable = [
        'user_id', 'username',
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
        'consultation_required',
        'consultation_required_at',
        'consultation_marked_by',
    ];

    protected $casts = [
        'consultation_required' => 'boolean',
        'consultation_required_at' => 'datetime',
    ];

    public function activities()
    {
        return $this->hasMany(FcOtActivity::class, 'user_id', 'username', 'user_id');
    }

    public function preHistory()
    {
        return $this->hasMany(FcPreHistory::class, 'user_id', 'username', 'user_id');
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
