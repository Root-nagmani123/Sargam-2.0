<?php

namespace Modules\Protocol\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleType extends Model
{
    protected $table = 'vehicle_types';

    protected $guarded = [];
    public $timestamps = false;
}
