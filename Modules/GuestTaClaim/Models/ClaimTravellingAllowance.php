<?php

namespace Modules\GuestTaClaim\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClaimTravellingAllowance extends Model
{
    protected $table = 'gtc_claim_travelling_allowances';

    protected $guarded = ['id'];

    protected $casts = [
        'departure_at' => 'datetime',
        'arrival_at' => 'datetime',
        'distance_km' => 'decimal:2',
        'fare_paid' => 'decimal:2',
        'fare_admissible' => 'decimal:2',
        'accommodation' => 'decimal:2',
    ];

    public function claim(): BelongsTo
    {
        return $this->belongsTo(Claim::class);
    }

    public function toFormArray(): array
    {
        return [
            'departure_at' => $this->departure_at?->format('Y-m-d\TH:i'),
            'departure_from' => $this->departure_from,
            'arrival_at' => $this->arrival_at?->format('Y-m-d\TH:i'),
            'arrival_to' => $this->arrival_to,
            'travel_mode' => $this->travel_mode,
            'distance_km' => $this->distance_km,
            'fare_paid' => $this->fare_paid,
            'fare_admissible' => $this->fare_admissible,
            'accommodation' => $this->accommodation,
        ];
    }
}
