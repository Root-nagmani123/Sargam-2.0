<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nomination extends Model
{
    protected $table = 'nomination';
    protected $primaryKey = 'pk';
    public $timestamps = false;
    protected $guarded = [];

    public const STATUS_PENDING   = 'pending';
    public const STATUS_ACCEPTED  = 'accepted';
    public const STATUS_WITHDRAWN = 'withdrawn';

    public function drive()
    {
        return $this->belongsTo(NominationDrive::class, 'nomination_drive_pk', 'pk');
    }

    public function clubSociety()
    {
        return $this->belongsTo(ClubSocietyMaster::class, 'club_society_master_pk', 'pk');
    }

    public function role()
    {
        return $this->belongsTo(ClubSocietyRoleMaster::class, 'club_society_role_master_pk', 'pk');
    }
}
