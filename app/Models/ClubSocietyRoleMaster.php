<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClubSocietyRoleMaster extends Model
{
    protected $table = 'club_society_role_master';

    protected $primaryKey = 'pk';

    /** created_date / updated_date are maintained by the database. */
    public $timestamps = false;

    protected $guarded = [];

    public function scopeActive($query)
    {
        return $query->where('active_inactive', 1);
    }
}
