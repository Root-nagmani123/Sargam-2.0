<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VenueMaster extends Model
{
    protected $table = 'venue_master';
    protected $primaryKey = 'venue_id';
    public $timestamps = false;

    protected $fillable = [
        'venue_name',
        'description',
        'venue_short_name',
        'created_date',
        'modified_date',
        'active_inactive',
    ];
}
