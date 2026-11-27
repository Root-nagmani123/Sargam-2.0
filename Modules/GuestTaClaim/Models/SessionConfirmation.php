<?php

namespace Modules\GuestTaClaim\Models;

use Illuminate\Database\Eloquent\Model;

class SessionConfirmation extends Model
{
    protected $table = 'gtc_session_confirmations';

    protected $guarded = ['id'];

    protected $casts = [
        'sessions' => 'array',
        'period_from' => 'date',
        'period_to' => 'date',
        'confirmed_at' => 'datetime',
    ];
}
