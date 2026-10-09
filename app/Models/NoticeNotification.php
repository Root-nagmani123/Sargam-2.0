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

    /**
     * The single selected course, when there is exactly one.
     *
     * Null for a multi-course notice — courses() is the full list. Kept because
     * the scalar column is still written for anything outside this module.
     */
    public function course()
    {
        return $this->belongsTo(\App\Models\CourseMaster::class, 'course_master_pk', 'pk');
    }

    public function department()
    {
        return $this->belongsTo(\App\Models\DepartmentMaster::class, 'department_master_pk', 'pk');
    }

    /** Every audience row: courses, groups, departments and individual people. */
    public function audienceMaps()
    {
        return $this->hasMany(\App\Models\NoticeAudienceMap::class, 'notices_notification_pk', 'pk');
    }

    /** Only the individually-picked recipients (audience_mode = individual). */
    public function individualMaps()
    {
        return $this->audienceMaps()->whereIn('audience_type', [
            NoticeAudienceMap::TYPE_STUDENT,
            NoticeAudienceMap::TYPE_EMPLOYEE,
        ]);
    }

    public function courses()
    {
        return $this->belongsToMany(
            \App\Models\CourseMaster::class,
            'notice_audience_map',
            'notices_notification_pk',
            'reference_pk'
        )->wherePivot('audience_type', NoticeAudienceMap::TYPE_COURSE);
    }

    public function departments()
    {
        return $this->belongsToMany(
            \App\Models\DepartmentMaster::class,
            'notice_audience_map',
            'notices_notification_pk',
            'reference_pk'
        )->wherePivot('audience_type', NoticeAudienceMap::TYPE_DEPARTMENT);
    }

    public function groupTypeMaps()
    {
        return $this->belongsToMany(
            \App\Models\GroupTypeMasterCourseMasterMap::class,
            'notice_audience_map',
            'notices_notification_pk',
            'reference_pk'
        )->wherePivot('audience_type', NoticeAudienceMap::TYPE_GROUP);
    }

    public function isOfficerTraineeAudience(): bool
    {
        return stripos((string) $this->target_audience, 'Office trainee') !== false;
    }

    public function isStaffFacultyAudience(): bool
    {
        return stripos((string) $this->target_audience, 'Staff/Faculty') !== false;
    }

    /**
     * Comma-separated course names, or "All courses" when none are pinned.
     * Returns null when the notice is not aimed at Officer Trainees.
     *
     * A notice saved before audience targeting (audience_mode NULL) with no course
     * reaches no trainee (PR #334 F-046), so it must not be listed as "All courses"
     * (F-032).
     */
    public function getCourseLabelAttribute(): ?string
    {
        if (! $this->isOfficerTraineeAudience()) {
            return null;
        }

        $names = $this->courses->pluck('course_name')->filter();

        if ($names->isEmpty()) {
            return $this->audience_mode === null ? 'No course (not shown to Officer Trainees)' : 'All courses';
        }

        return $names->implode(', ');
    }

    public function getDepartmentLabelAttribute(): ?string
    {
        if (! $this->isStaffFacultyAudience()) {
            return null;
        }

        $names = $this->departments->pluck('department_name')->filter();

        return $names->isEmpty() ? 'All departments' : $names->implode(', ');
    }

    /** Group names for a group-targeted notice, e.g. ["Lecture Group - A"]. */
    public function getGroupLabelsAttribute()
    {
        return $this->groupTypeMaps->map(function ($map) {
            return trim(($map->courseGroupType->type_name ?? 'Group') . ' - ' . $map->group_name, ' -');
        })->filter()->values();
    }

    /**
     * Human-readable audience for list screens, e.g.
     * "Office trainee — IAS 2024 / Lecture Group - A".
     */
    public function getAudienceSummaryAttribute(): string
    {
        $audience = (string) $this->target_audience;

        if ($this->isOfficerTraineeAudience()) {
            $parts = [$this->course_label];
        } elseif ($this->isStaffFacultyAudience()) {
            $parts = [$this->department_label];
        } else {
            return $audience;
        }

        if ($this->audience_mode === self::MODE_GROUP) {
            $groups = $this->group_labels;
            $parts[] = $groups->isEmpty() ? 'Group' : $groups->implode(', ');
        } elseif ($this->audience_mode === self::MODE_INDIVIDUAL) {
            $parts[] = $this->individualMaps()->count() . ' selected';
        }

        return $audience . ' — ' . implode(' / ', $parts);
    }
}
