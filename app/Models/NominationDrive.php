<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NominationDrive extends Model
{
    protected $table = 'nomination_drive';
    protected $primaryKey = 'pk';
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = [
        'start_date'                 => 'date',
        'end_date'                   => 'date',
        'self_nomination_allow'      => 'boolean',
        'nomination_accept_status'   => 'boolean',
        'nomination_withdraw_status' => 'boolean',
    ];

    public function course()
    {
        return $this->belongsTo(CourseMaster::class, 'course_master_pk', 'pk');
    }

    public function societies()
    {
        return $this->hasMany(NominationDriveSociety::class, 'nomination_drive_pk', 'pk');
    }

    public function nominations()
    {
        return $this->hasMany(Nomination::class, 'nomination_drive_pk', 'pk');
    }

    public function scopeActive($query)
    {
        return $query->where('active_inactive', 1);
    }
}
