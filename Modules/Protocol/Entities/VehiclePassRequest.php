<?php

namespace Modules\Protocol\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class VehiclePassRequest extends Model
{
    protected $table = 'protocol_vehicle_passes';

    protected $fillable = [
        'vehicle_type',
        'one_way_booking',
        'payment_will_be_done_by',
    ];

    protected $casts = [
        'one_way_booking' => 'boolean',
    ];

    public function protocolRequest(): MorphOne
    {
        return $this->morphOne(ProtocolRequest::class, 'requestable');
    }

    /** The repeatable Date/Pickup/Drop rows under this vehicle pass. */
    public function legs(): HasMany
    {
        return $this->hasMany(VehicleLeg::class, 'vehicle_pass_id')->orderBy('sort_order');
    }
}
