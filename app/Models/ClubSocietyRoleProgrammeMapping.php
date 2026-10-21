<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClubSocietyRoleProgrammeMapping extends Model
{
    protected $table = 'club_society_role_programme_mapping';

    protected $primaryKey = 'pk';

    /** created_date / updated_date are maintained by the database. */
    public $timestamps = false;

    // Status, publish and active flags are deliberately not mass-assignable:
    // they are set explicitly by the controller actions that own them (PR #340 F-010).
    protected $fillable = [
        'course_master_pk',
        'club_society_master_pk',
        'club_society_role_master_pk',
        'number_of_post',
        'required_nomination',
        'number_of_nomination',
    ];

    protected $casts = [
        'required_nomination'  => 'boolean',
        'number_of_post'       => 'integer',
        'number_of_nomination' => 'integer',
    ];

    public function course()
    {
        return $this->belongsTo(CourseMaster::class, 'course_master_pk', 'pk');
    }

    public function clubSociety()
    {
        return $this->belongsTo(ClubSocietyMaster::class, 'club_society_master_pk', 'pk');
    }

    public function role()
    {
        return $this->belongsTo(ClubSocietyRoleMaster::class, 'club_society_role_master_pk', 'pk');
    }

    public function scopeActive($query)
    {
        return $query->where('active_inactive', 1);
    }
}
