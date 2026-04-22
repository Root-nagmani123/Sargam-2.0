<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class QualificationMaster extends Model {
    protected $table = 'qualification_master';
    protected $primaryKey = 'pk';
    protected $fillable = ['qualification_name'];
}
