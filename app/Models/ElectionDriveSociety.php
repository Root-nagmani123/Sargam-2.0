<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElectionDriveSociety extends Model
{
    protected $table = 'election_drive_society';
    protected $primaryKey = 'pk';
    public $timestamps = false;
    protected $guarded = [];

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
