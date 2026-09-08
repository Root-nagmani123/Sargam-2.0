<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NominationDriveSocietyPost extends Model
{
    protected $table = 'nomination_drive_society_post';
    protected $primaryKey = 'pk';
    public $timestamps = false;
    protected $guarded = [];

    public function society()
    {
        return $this->belongsTo(NominationDriveSociety::class, 'nomination_drive_society_pk', 'pk');
    }

    public function role()
    {
        return $this->belongsTo(ClubSocietyRoleMaster::class, 'club_society_role_master_pk', 'pk');
    }
}
