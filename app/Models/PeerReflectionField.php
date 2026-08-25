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
        'group_id',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function course(): BelongsTo
    {
        // course_id is a course_master.pk since
        // 2026_08_24_000002_point_peer_evaluation_at_course_master; course_master
        // keys on `pk`, not `id`, so the owner key has to be named explicitly.
        return $this->belongsTo(CourseMaster::class, 'course_id', 'pk');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(PeerEvent::class, 'event_id');
    }

    /**
     * Optional group scope. All three of course/event/group are nullable: a field
     * with none set is a GLOBAL field shown on every evaluation form, which is how
     * the seeded "Overall comment" style fields work.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(PeerGroup::class, 'group_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
