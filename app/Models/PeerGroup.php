<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PeerGroup extends Model
{
    protected $table = 'peer_groups';
    
    protected $fillable = [
        'group_name', 
        'course_id',
        'event_id',
        'is_active', 
        'is_form_active', 
        'max_marks'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_form_active' => 'boolean',
        'max_marks' => 'decimal:2',
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

    public function members(): HasMany
    {
        return $this->hasMany(PeerGroupMember::class, 'group_id');
    }

    // Get member count attribute
    public function getMembersCountAttribute()
    {
        return $this->members()->count();
    }

    // Scope for active groups
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope for form active groups
    public function scopeFormActive($query)
    {
        return $query->where('is_form_active', true);
    }
}