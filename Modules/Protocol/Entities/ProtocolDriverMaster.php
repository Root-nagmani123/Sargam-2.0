<?php

namespace Modules\Protocol\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
class ProtocolDriverMaster extends Model
{
   use SoftDeletes; 

   protected $fillable = [
    'name',
    'type',
    'dob',
    'driver_no',
    'driving_licence_no',
    'licence_expiry_date',
    'hiring_date',
    'helper_two',
    'helper_one',
    'note_about_driver',
    'country_id',
    'state_id',
    'city_id',
    'postal_code',
    'email',
    'address',
];

    protected $casts = [
        'dob' => 'date',
        'licence_expiry_date' => 'date',
        'hiring_date' => 'date',
    ];
}
