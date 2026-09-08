<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElectionDrive extends Model
{
    protected $table = 'election_drive';
    protected $primaryKey = 'pk';
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = [
        'drive_date'              => 'date',
        'same_date_for_all'       => 'boolean',
        'same_time_for_all'       => 'boolean',
        'election_publish_status' => 'boolean',
        'result_status'           => 'boolean',
    ];

    public function nominationDrive()
    {
        return $this->belongsTo(NominationDrive::class, 'nomination_drive_pk', 'pk');
    }

    public function societies()
    {
        return $this->hasMany(ElectionDriveSociety::class, 'election_drive_pk', 'pk');
    }

    public function scopeActive($query)
    {
        return $query->where('active_inactive', 1);
    }
}
