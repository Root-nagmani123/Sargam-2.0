<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $table = 'city_master';
    protected $primaryKey = 'pk';
    public $timestamps = true;
    
    protected $fillable = [
        'state_master_pk',
        'district_master_pk',
        'city_name',
    ];
    
}
