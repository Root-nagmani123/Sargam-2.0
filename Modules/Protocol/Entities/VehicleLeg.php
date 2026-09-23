<?php

namespace Modules\Protocol\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleLeg extends Model
{
    protected $table = 'protocol_vehicle_legs';

    protected $fillable = [
        'vehicle_pass_id',
        'date_time_from',
        'date_time_to',
        'pickup_type',
        'pickup_value',
        'pickup_time',
        'drop_type',
        'drop_value',
        'drop_time',
        'no_of_persons',
        'remarks',
        'sort_order',
    ];

    protected $casts = [
        'date_time_from' => 'datetime',
        'date_time_to' => 'datetime',
    ];

    public function vehiclePass(): BelongsTo
    {
        return $this->belongsTo(VehiclePassRequest::class, 'vehicle_pass_id');
    }

    public function pickupLabel(): string
    {
        return $this->pickup_type === 'reference'
            ? "{$this->pickup_value} (Train/Flight/Guest House)"
            : $this->pickup_value;
    }

    public function dropLabel(): string
    {
        return $this->drop_type === 'reference'
            ? "{$this->drop_value} (Train/Flight/Guest House)"
            : $this->drop_value;
    }
}
