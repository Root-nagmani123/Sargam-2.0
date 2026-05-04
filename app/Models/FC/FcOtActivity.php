<?php

namespace App\Models\FC;

use Illuminate\Database\Eloquent\Model;

class FcOtActivity extends Model
{
    protected $table = 'fc_otactivity_details';

    protected $fillable = [
        'activityid',
        'username',
        'activity',
        'activityval',
        'activitydt',
        'submitedby',
        'course',
        'status',
    ];

    public function ot()
    {
        return $this->belongsTo(FcOtDetail::class, 'username', 'username');
    }

    public function activityMaster()
    {
        return $this->belongsTo(FcActivityMaster::class, 'activity', 'menuid');
    }
}
