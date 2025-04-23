<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    protected $table = 'state_district_mapping';
    protected $primaryKey = 'pk';
    public $timestamps = false;

    protected $fillable = [
        'state_master_pk',
        'district_name',
    ];
}
