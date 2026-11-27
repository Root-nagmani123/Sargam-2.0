<?php

namespace Modules\GuestTaClaim\Models;

use Illuminate\Database\Eloquent\Model;

class ClaimSession extends Model
{
    protected $table = 'gtc_claim_sessions';

    protected $guarded = ['id'];

    protected $casts = [
        'session_date' => 'date',
        'is_edited' => 'boolean',
    ];

    public function claim()
    {
        return $this->belongsTo(Claim::class);
    }

    public function toFormArray(): array
    {
        return [
            'source_session_id' => $this->source_session_id,
            'session_date' => $this->session_date->format('Y-m-d'),
            'start_time' => substr((string) $this->start_time, 0, 5),
            'end_time' => substr((string) $this->end_time, 0, 5),
            'topic' => $this->topic,
            'venue' => $this->venue,
            'status' => $this->status,
        ];
    }
}
