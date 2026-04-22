<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class EcmMaster extends Model {
    protected $table = 'ecm_masters';
    protected $fillable = ['ecm_name'];
}
