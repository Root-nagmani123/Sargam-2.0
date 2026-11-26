<?php

namespace Modules\Protocol\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProtocolRequestDriver extends Model
{
    protected $table = 'protocol_request_drivers';

    protected $fillable = [
        'protocol_request_id',
        'vehicle_no',
        'vehicle_name',
        'driver_name',
        'driver_number',
    ];

    public function protocolRequest(): BelongsTo
    {
        return $this->belongsTo(ProtocolRequest::class, 'protocol_request_id');
    }
}
