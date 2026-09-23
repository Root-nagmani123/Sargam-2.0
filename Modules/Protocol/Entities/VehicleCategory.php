<?php

namespace Modules\Protocol\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleCategory extends Model
{
    protected $table = 'vehicle_categories';

    protected $guarded = [];
    public $timestamps = false;
}
