<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeerColumn extends Model
{
    protected $table = 'peer_columns';
    
    protected $fillable = [
        'column_name',
        'course_id',
        'event_id',
        'is_visible'
    ];

    protected $casts = [
        'is_visible' => 'boolean',
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
}