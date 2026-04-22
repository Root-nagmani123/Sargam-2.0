<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class HighestStreamMaster extends Model {
    protected $table = 'stream_master';
    protected $primaryKey = 'pk';
    protected $fillable = ['stream_name'];
}
