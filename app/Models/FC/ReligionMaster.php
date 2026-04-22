<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class ReligionMaster extends Model {
    protected $table = 'religion_master';
    protected $primaryKey = 'pk';
    protected $fillable = ['religion_name','religion_name_hindi'];
    // protected $fillable = ['religion_name'];
}
