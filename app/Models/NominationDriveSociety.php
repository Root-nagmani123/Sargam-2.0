<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NominationDriveSociety extends Model
{
    protected $table = 'nomination_drive_society';
    protected $primaryKey = 'pk';
    public $timestamps = false;
    protected $guarded = [];

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
