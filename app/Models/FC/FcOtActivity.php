<?php

namespace App\Models\FC;
use App\Models\FC\Concerns\FcUserAware;

use Illuminate\Database\Eloquent\Model;

class FcOtActivity extends Model
{
    use FcUserAware;
    protected $table = 'fc_otactivity_details';

    protected $fillable = [
        'activityid',
        'user_id',   // post-migration
        'username',  // pre-migration column name for fc_otactivity_details
        'activity',
        'activityval',
        'activitydt',
        'submitedby',
        'course',
        'status',
    ];

    public function ot()
    {
        $localKey = fc_user_col($this->getTable());
        $foreignKey = fc_user_col((new FcOtDetail)->getTable());

        return $this->belongsTo(FcOtDetail::class, $localKey, $foreignKey);
    }

    public function activityMaster()
    {
        return $this->belongsTo(FcActivityMaster::class, 'activity', 'menuid');
    }
}
