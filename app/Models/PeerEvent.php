<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PeerEvent extends Model
{
    protected $table = 'peer_events';

    protected $fillable = [
        'event_name',
        'course_id',
        'start_date',
        'end_date',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'course_id' => 'integer',
        // date, not datetime: the schedule is day-granular and the grid, the
        // exports and the modals all format it as d/m/Y.
        'start_date' => 'date',
        'end_date' => 'date',
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

    public function groups(): HasMany
    {
        return $this->hasMany(PeerGroup::class, 'event_id');
    }

    public function columns(): HasMany
    {
        return $this->hasMany(PeerColumn::class, 'event_id');
    }

    // Scope for active events
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
