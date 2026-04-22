<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class SportsMaster extends Model {
    protected $table = 'sports_masters';
    protected $fillable = ['sport_name'];
}
