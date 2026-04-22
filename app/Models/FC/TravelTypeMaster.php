<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class TravelTypeMaster extends Model {
    protected $table = 'travel_type_masters';
    protected $fillable = ['travel_type_name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
