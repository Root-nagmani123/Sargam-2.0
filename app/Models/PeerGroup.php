<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class PeerGroup extends Model
{
    protected $table = 'peer_groups';
    
    protected $fillable = [
        'group_name', 
        'is_active', 
        'is_form_active', 
        'max_marks', 
        'created_at', 
        'updated_at'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_form_active' => 'boolean',
        'max_marks' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relationship to members
    public function members(): HasMany
    {
        return $this->hasMany(PeerGroupMember::class, 'group_id');
    }

    // Get member count attribute
    public function getMemberCountAttribute()
    {
        return $this->members()->count();
    }

    // Static method to get groups with user info
    public static function getActiveGroupsWithUser($userId)
    {
        return self::leftJoin('peer_group_members as m', 'peer_groups.id', '=', 'm.group_id')
            ->where('peer_groups.is_form_active', 1)
            ->where('m.user_id', $userId)
            ->select(
                'peer_groups.id',
                'peer_groups.group_name',
                'peer_groups.max_marks',
                DB::raw('GROUP_CONCAT(m.course_name SEPARATOR ", ") as course_names'),
                DB::raw('GROUP_CONCAT(m.event_name SEPARATOR ", ") as event_names'),
                DB::raw('GROUP_CONCAT(m.ot_code SEPARATOR ", ") as ot_codes')
            )
            ->groupBy('peer_groups.id', 'peer_groups.group_name', 'peer_groups.max_marks')
            ->get();
    }

    // Get user's group IDs
    public static function getUserGroupIds($userId)
    {
        return PeerGroupMember::where('user_id', $userId)->pluck('group_id')->toArray();
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