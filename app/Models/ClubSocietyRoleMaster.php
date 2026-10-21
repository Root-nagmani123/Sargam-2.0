<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClubSocietyRoleMaster extends Model
{
    protected $table = 'club_society_role_master';

    protected $primaryKey = 'pk';

    /** created_date / updated_date are maintained by the database. */
    public $timestamps = false;

    // Status, publish and active flags are deliberately not mass-assignable:
    // they are set explicitly by the controller actions that own them (PR #340 F-010).
    protected $fillable = [
        'club_society_role_name',
    ];

    public function scopeActive($query)
    {
        return $query->where('active_inactive', 1);
    }
}
