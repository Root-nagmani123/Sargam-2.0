<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NoticeNotification extends Model
{
    use HasFactory;

    /** audience_mode values */
    public const MODE_ALL = 'all';
    public const MODE_GROUP = 'group';
    public const MODE_INDIVIDUAL = 'individual';

    protected $table = "notices_notification";

    protected $fillable = [
        'notice_title',
        'description',
        'notice_type',
        'display_date',
        'expiry_date',
        'document',
        'target_audience',
        'created_by',
        'active_inactive',
        'course_master_pk',
        'department_master_pk',
        'group_type_map_pk',
        'audience_mode',
    ];
    protected $primaryKey = 'pk';

    // Relationship with User table
    public function user()
    {
        return $this->belongsTo(\App\Models\UserCredential::class, 'created_by', 'pk');
    }
    public function course()
    {
        return $this->belongsTo(\App\Models\CourseMaster::class, 'course_master_pk', 'pk');
    }

    public function department()
    {
        return $this->belongsTo(\App\Models\DepartmentMaster::class, 'department_master_pk', 'pk');
    }

    /** The course+group row the notice targets (null unless audience_mode = group). */
    public function groupTypeMap()
    {
        return $this->belongsTo(\App\Models\GroupTypeMasterCourseMasterMap::class, 'group_type_map_pk', 'pk');
    }

    /** Individually-picked recipients (only present when audience_mode = individual). */
    public function audienceMaps()
    {
        return $this->hasMany(\App\Models\NoticeAudienceMap::class, 'notices_notification_pk', 'pk');
    }

    /**
     * Human-readable audience for list screens, e.g.
     * "Office trainee — IAS 2024 / Lecture Group - A" or "Staff/Faculty — All departments".
     */
    public function getAudienceSummaryAttribute(): string
    {
        $audience = (string) $this->target_audience;

        if (stripos($audience, 'Office trainee') !== false) {
            $parts = [$this->course_master_pk ? ($this->course->course_name ?? 'Course') : 'All courses'];
        } elseif (stripos($audience, 'Staff/Faculty') !== false) {
            $parts = [$this->department_master_pk ? ($this->department->department_name ?? 'Department') : 'All departments'];
        } else {
            return $audience;
        }

        if ($this->audience_mode === self::MODE_GROUP && $this->groupTypeMap) {
            $parts[] = trim(($this->groupTypeMap->courseGroupType->type_name ?? 'Group') . ' - ' . $this->groupTypeMap->group_name, ' -');
        } elseif ($this->audience_mode === self::MODE_INDIVIDUAL) {
            $count = $this->audience_maps_count ?? $this->audienceMaps()->count();
            $parts[] = $count . ' selected';
        }

        return $audience . ' — ' . implode(' / ', $parts);
    }
}
