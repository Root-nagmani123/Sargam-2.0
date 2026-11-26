<?php

namespace Modules\Protocol\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProtocolTicketFile extends Model
{
    protected $table = 'protocol_ticket_files';

    protected $fillable = [
        'protocol_request_id',
        'uploaded_by_user_id',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
    ];

    public function protocolRequest(): BelongsTo
    {
        return $this->belongsTo(ProtocolRequest::class, 'protocol_request_id');
    }
}
