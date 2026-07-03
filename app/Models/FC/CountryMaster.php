<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class CountryMaster extends Model {
    protected $table = 'country_master';
    protected $primaryKey = 'pk';
    protected $fillable = ['country_name','country_code'];
}
