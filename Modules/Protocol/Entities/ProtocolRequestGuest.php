<?php

namespace Modules\Protocol\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProtocolRequestGuest extends Model
{
    protected $table = 'protocol_request_guests';

    protected $fillable = [
        'batch_id',
        'guest_name',
        'age',
        'sex',
        'designation',
        'mobile_no',
        'email_id',
        'guest_id_proof',
        'is_main_guest',
        'sort_order',
    ];

    protected $casts = [
        'is_main_guest' => 'boolean',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProtocolRequestBatch::class, 'batch_id');
    }
}
