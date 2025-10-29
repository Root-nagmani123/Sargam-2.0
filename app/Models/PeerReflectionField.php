<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeerReflectionField extends Model
{
    protected $table = 'peer_reflection_fields';

    protected $fillable = [
        'field_label',
        'course_id',
        'event_id',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(PeerCourse::class, 'course_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(PeerEvent::class, 'event_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
