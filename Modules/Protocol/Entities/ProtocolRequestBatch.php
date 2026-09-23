<?php

namespace Modules\Protocol\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProtocolRequestBatch extends Model
{
    protected $table = 'protocol_request_batches';

    protected $fillable = [ 
        'user_id',
        'employee_id',
        'employee_department',
        'course_team_to_notify',
        'purpose',
        'escort_required',
        'is_faculty',
    ];

    protected $casts = [
        'escort_required' => 'boolean',
        'is_faculty' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'employee_id');
    }

    /** All guest rows entered once on this batch, shared by every request type checked. */
    public function guests(): HasMany
    {
        return $this->hasMany(ProtocolRequestGuest::class, 'batch_id')->orderBy('sort_order');
    }

    public function mainGuest(): ?ProtocolRequestGuest
    {
        return $this->guests->firstWhere('is_main_guest', true) ?? $this->guests->first();
    }

    /** Every protocol_requests row (one per checked type) this batch produced. */
    public function requests(): HasMany
    {
        return $this->hasMany(ProtocolRequest::class, 'batch_id');
    }
}
