<?php

namespace Modules\Protocol\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class GuestHouseRequest extends Model
{
    protected $table = 'protocol_guest_houses';

    protected $fillable = [
        'date_from',
        'date_to',
        'no_of_guests',
        'no_of_rooms',
        'remarks',
        'payment_done_by',
        'assigned_guest_house',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
    ];

    public function protocolRequest(): MorphOne
    {
        return $this->morphOne(ProtocolRequest::class, 'requestable');
    }
}
