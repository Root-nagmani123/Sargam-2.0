<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveApplication extends Model
{
    public const STATUS_DRAFT = 0;

    public const STATUS_PENDING = 1;

    public const STATUS_APPROVED = 2;

    public const STATUS_REJECTED = 3;

    public const TYPE_PT_EXEMPTION = 'PT_EXEMPTION';

    public const TYPE_STATIONED_LEAVE = 'STATIONED_LEAVE';

    protected $table = 'leave_application';

    protected $primaryKey = 'pk';

    public $timestamps = false;

    protected $fillable = [
        'course_master_pk',
        'student_master_pk',
        'leave_type',
        'leave_nature_master_pk',
        'from_date',
        'to_date',
        'time_from',
        'time_to',
        'total_days',
        'reason',
        'contact_number',
        'status',
        'submitted_at',
        'approved_by_faculty_pk',
        'approved_at',
        'rejection_remarks',
        'applied_by_user_pk',
        'active_inactive',
        'created_date',
        'modified_date',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
        'total_days' => 'decimal:1',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function course()
    {
        return $this->belongsTo(CourseMaster::class, 'course_master_pk', 'pk');
    }

    public function student()
    {
        return $this->belongsTo(StudentMaster::class, 'student_master_pk', 'pk');
    }

    public function nature()
    {
        return $this->belongsTo(LeaveNatureMaster::class, 'leave_nature_master_pk', 'pk');
    }

    public function attachments()
    {
        return $this->hasMany(LeaveApplicationAttachment::class, 'leave_application_pk', 'pk');
    }

    public function approvedByFaculty()
    {
        return $this->belongsTo(FacultyMaster::class, 'approved_by_faculty_pk', 'pk');
    }

    /**
     * Training-section operator who recorded this leave for the officer trainee.
     * Null for the normal case where the officer trainee applied for themselves.
     */
    public function appliedByUser()
    {
        return $this->belongsTo(User::class, 'applied_by_user_pk', 'pk');
    }

    public function getAppliedOnBehalfAttribute(): bool
    {
        return ! empty($this->applied_by_user_pk);
    }

    /**
     * "<name> (Training Section)" for leave entered on behalf of an officer trainee,
     * or null when the officer trainee applied for themselves.
     */
    protected function onBehalfActorName(): ?string
    {
        if (empty($this->applied_by_user_pk)) {
            return null;
        }

        $actor = $this->appliedByUser;

        if (! $actor) {
            return 'Training Section';
        }

        $name = trim(implode(' ', array_filter([
            $actor->first_name ?? '',
            $actor->last_name ?? '',
        ]))) ?: ($actor->user_name ?? '');

        return trim($name) === ''
            ? 'Training Section'
            : $name . ' (Training Section)';
    }

    public function getActionByFacultyNameAttribute(): string
    {
        $faculty = $this->approvedByFaculty;

        if (! $faculty) {
            // Leave recorded by the training section carries no faculty approver —
            // the Course Coordinator approved it offline. Name the operator who
            // entered it rather than showing a bare "-" against an approved record.
            return $this->onBehalfActorName() ?? '-';
        }

        $name = trim((string) ($faculty->full_name ?? ''));
        if ($name !== '') {
            return $name;
        }

        return trim(implode(' ', array_filter([
            $faculty->first_name ?? '',
            $faculty->last_name ?? '',
        ]))) ?: '-';
    }

    /**
     * Departure / return time as "hh:mm AM" for display, or "-" when the
     * application carries none (PT exemptions, and anything recorded before the
     * time fields existed). One accessor keeps the two lists, the detail view and
     * both exports showing the same thing.
     */
    public function getTimeFromDisplayAttribute(): string
    {
        return $this->formatTime($this->time_from);
    }

    public function getTimeToDisplayAttribute(): string
    {
        return $this->formatTime($this->time_to);
    }

    protected function formatTime($value): string
    {
        if (blank($value)) {
            return '-';
        }

        try {
            return \Carbon\Carbon::parse($value)->format('h:i A');
        } catch (\Throwable $e) {
            return '-';
        }
    }

    public function getLeaveTypeLabelAttribute(): string
    {
        return match ($this->leave_type) {
            self::TYPE_PT_EXEMPTION => 'PT Exemption',
            self::TYPE_STATIONED_LEAVE => 'Stationed Leave',
            default => $this->leave_type,
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ((int) $this->status) {
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            default => 'Unknown',
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ((int) $this->status) {
            self::STATUS_PENDING => 'bg-warning text-dark',
            self::STATUS_APPROVED => 'bg-success',
            self::STATUS_REJECTED => 'bg-danger',
            self::STATUS_DRAFT => 'bg-secondary',
            default => 'bg-light text-dark',
        };
    }
}
