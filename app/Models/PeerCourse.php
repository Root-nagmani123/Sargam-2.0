<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PeerCourse extends Model
{
    protected $table = 'peer_courses';
    
    protected $fillable = ['course_name', 'event_id', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(PeerEvent::class, 'event_id');
    }

    public function groups(): HasMany
    {
        return $this->hasMany(PeerGroup::class, 'course_id');
    }

    public function columns(): HasMany
    {
        return $this->hasMany(PeerColumn::class, 'course_id');
    }

    // Scope for active courses
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}