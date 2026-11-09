<?php

namespace Modules\Protocol\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketJourney extends Model
{
    protected $table = 'protocol_ticket_journeys';

    protected $fillable = [
        'ticket_id',
        'origin',
        'destination',
        'ticket_type',
        'class',
        'date_of_journey',
        'train_flight_bus_name',
        'train_flight_bus_no',
        'quota',
        'book_if_waiting',
        'payment_will_be_done_by',
        'remarks',
        'sort_order',
    ];

    protected $casts = [
        'date_of_journey' => 'date',
        'book_if_waiting' => 'boolean',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(TicketRequest::class, 'ticket_id');
    }
}
