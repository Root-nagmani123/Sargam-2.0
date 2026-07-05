<?php

namespace App\Models\FC;

use App\Models\FC\Concerns\FcUserAware;
use App\Models\StudentMaster;
use App\Models\UserCredential;
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

    /**
     * Trainee when fc_otactivity_details.user_id = student_master.pk (no credentials row).
     */
    public function studentMasterDirect()
    {
        $actUserCol = fc_user_col($this->getTable());

        return $this->belongsTo(StudentMaster::class, $actUserCol, 'pk');
    }

    /**
     * Trainee when fc_otactivity_details.user_id = user_credentials.pk.
     */
    public function studentViaCredentials()
    {
        $actUserCol = fc_user_col($this->getTable());

        return $this->hasOneThrough(
            StudentMaster::class,
            UserCredential::class,
            'pk',
            'pk',
            $actUserCol,
            'user_id'
        );
    }

    public function studentMaster()
    {
        return $this->studentViaCredentials();
    }

    /** @alias studentViaCredentials() Legacy name used in some views. */
    public function ot()
    {
        return $this->studentViaCredentials();
    }

    public function activityMaster()
    {
        return $this->belongsTo(FcActivityMaster::class, 'activity', 'menuid');
    }
}
