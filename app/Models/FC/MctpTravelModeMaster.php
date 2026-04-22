<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class MctpTravelModeMaster extends Model {
    protected $table = 'mctp_travel_mode_masters';
    protected $fillable = ['travel_mode_name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
