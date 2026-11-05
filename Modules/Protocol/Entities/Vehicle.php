<?php

namespace Modules\Protocol\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vehicle extends Model
{
    protected $table = 'protocol_vehicle_masters';

    protected $guarded = [];
    public $timestamps = false;
}
