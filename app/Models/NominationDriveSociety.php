<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NominationDriveSociety extends Model
{
    protected $table = 'nomination_drive_society';
    protected $primaryKey = 'pk';
    public $timestamps = false;
    // Status, publish and active flags are deliberately not mass-assignable:
    // they are set explicitly by the controller actions that own them (PR #340 F-010).
    protected $fillable = [
        'nomination_drive_pk',
        'club_society_master_pk',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    public function drive()
    {
        return $this->belongsTo(NominationDrive::class, 'nomination_drive_pk', 'pk');
    }

    public function clubSociety()
    {
        return $this->belongsTo(ClubSocietyMaster::class, 'club_society_master_pk', 'pk');
    }

    public function posts()
    {
        return $this->hasMany(NominationDriveSocietyPost::class, 'nomination_drive_society_pk', 'pk');
    }
}
