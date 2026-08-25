<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeerColumn extends Model
{
    protected $table = 'peer_columns';
    
    /** The two rating types Manage Evaluation Columns splits its tabs by. */
    public const TYPE_RATE_PEERS = 'rate_peers';
    public const TYPE_DISTRIBUTE_MARKS = 'distribute_marks';

    public const TYPES = [
        self::TYPE_RATE_PEERS => 'Rate Peers',
        self::TYPE_DISTRIBUTE_MARKS => 'Distribute Marks',
    ];

    protected $fillable = [
        'column_name',
        'course_id',
        'event_id',
        'group_id',
        'max_marks',
        'has_remarks',
        'evaluation_type',
        'is_visible'
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'has_remarks' => 'boolean',
        'max_marks' => 'decimal:2',
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

    // Scope for visible columns
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    // Scope for global columns (no course/event association)
    public function scopeGlobal($query)
    {
        return $query->whereNull('course_id')->whereNull('event_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(PeerGroup::class, 'group_id');
    }

    public function scopeOfType($query, ?string $type)
    {
        return $type === null ? $query : $query->where('evaluation_type', $type);
    }

    /** Human label for the rating type, falling back rather than rendering blank. */
    public function getEvaluationTypeLabelAttribute(): string
    {
        return self::TYPES[$this->evaluation_type] ?? self::TYPES[self::TYPE_RATE_PEERS];
    }
}