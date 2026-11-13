<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElectionDriveSociety extends Model
{
    protected $table = 'election_drive_society';
    protected $primaryKey = 'pk';
    public $timestamps = false;
    // Status, publish and active flags are deliberately not mass-assignable:
    // they are set explicitly by the controller actions that own them (PR #340 F-010).
    protected $fillable = [
        'election_drive_pk',
        'club_society_master_pk',
        'drive_date',
        'start_time',
        'end_time',
    ];

    protected $casts = ['drive_date' => 'date'];

    public function electionDrive()
    {
        return $this->belongsTo(ElectionDrive::class, 'election_drive_pk', 'pk');
    }

    public function clubSociety()
    {
        return $this->belongsTo(ClubSocietyMaster::class, 'club_society_master_pk', 'pk');
    }
}
