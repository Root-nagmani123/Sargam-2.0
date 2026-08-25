<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PeerGroup extends Model
{
    protected $table = 'peer_groups';
    
    protected $fillable = [
        // The Course Group Mapping row this peer group represents
        // (group_type_master_course_master_map.pk). See App\Support\PeerGroupSource.
        'group_map_pk',
        'group_name', 
        'course_id',
        'event_id',
        'is_active', 
        'is_form_active', 
        'max_marks',
        // The pool an OT distributes across this group under "Distribute Marks".
        // Group-level, not per column, which is why every column of a group shows
        // the same figure on Manage Evaluation Columns.
        'buffer_marks'
    ];

    protected $casts = [
        'group_map_pk' => 'integer',
        'is_active' => 'boolean',
        'is_form_active' => 'boolean',
        'max_marks' => 'decimal:2',
        'buffer_marks' => 'decimal:2',
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

    public function members(): HasMany
    {
        return $this->hasMany(PeerGroupMember::class, 'group_id');
    }

    public function columns(): HasMany
    {
        return $this->hasMany(PeerColumn::class, 'group_id');
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