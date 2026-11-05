<?php

namespace Modules\Protocol\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class TicketRequest extends Model
{
    protected $table = 'protocol_tickets';

    protected $fillable = [];

    public function protocolRequest(): MorphOne
    {
        return $this->morphOne(ProtocolRequest::class, 'requestable');
    }

    /** The repeatable "For Ticket Booking" journeys under this ticket request. */
    public function journeys(): HasMany
    {
        return $this->hasMany(TicketJourney::class, 'ticket_id')->orderBy('sort_order');
    }
}
