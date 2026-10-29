<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * COE → Examination Management → Examination Drive Configuration.
 *
 * Backed by the existing `examination_drives` table (Laravel `id` PK,
 * `created_date`/`modified_date` instead of the framework's timestamps).
 * `status` is a tinyint: 0 Draft, 1 Active, 2 Closed (see STATUSES).
 */
class ExaminationDrive extends Model
{
    protected $table = 'examination_drives';

    protected $guarded = [];

    public $timestamps = false;

    public const STATUS_DRAFT = 0;
    public const STATUS_ACTIVE = 1;
    public const STATUS_CLOSED = 2;

    // Course Coordinator approval (cc_status); null = not sent to the CC yet.
    public const CC_PENDING = 'pending';
    public const CC_APPROVED = 'approved';
    public const CC_REJECTED = 'rejected';

    public const CC_STATUSES = [
        self::CC_PENDING => 'Pending at CC',
        self::CC_APPROVED => 'Approved by CC',
        self::CC_REJECTED => 'Rejected by CC',
    ];

    public const STATUSES = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_ACTIVE => 'Active',
        self::STATUS_CLOSED => 'Closed',
    ];

    protected $casts = [
        'status' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'proposed_start_date' => 'date',
        'proposed_end_date' => 'date',
        'cc_action_at' => 'datetime',
    ];

    public function examinationType(): BelongsTo
    {
        return $this->belongsTo(ExaminationTypeMaster::class, 'examination_type_master_pk', 'pk');
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(TermMaster::class, 'term_master_pk', 'pk');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(CourseMaster::class, 'course_master_pk', 'pk');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(ServiceMaster::class, 'service_master_pk', 'pk');
    }

    public function componentMaps(): HasMany
    {
        return $this->hasMany(ExaminationDriveComponentMap::class, 'examination_drive_id');
    }

    public function facultyMaps(): HasMany
    {
        return $this->hasMany(ExaminationDriveFacultyMap::class, 'examination_drive_id');
    }

    /**
     * Drive faculty: everyone mapped in the Create/Edit modal's Subject ->
     * Faculty rows. Needs facultyMaps.faculty loaded.
     *
     * @return string[]
     */
    public function driveFacultyNames(): array
    {
        return $this->facultyMaps
            ->map(fn ($map) => trim((string) ($map->faculty->full_name ?? '')))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Every sub-coordinator across the drive's components, de-duplicated.
     * Needs componentMaps.faculty loaded.
     *
     * @return string[]
     */
    public function subCoordinatorNames(): array
    {
        return $this->componentMaps
            ->flatMap(fn ($map) => $map->subCoordinatorNames())
            ->unique()
            ->values()
            ->all();
    }

    public function ccStatusLabel(): string
    {
        return self::CC_STATUSES[$this->cc_status] ?? 'Not sent to CC';
    }

    /**
     * Where the drive is in its workflow, shown on every drive list (admin,
     * faculty, COE, CC) and the Map Faculty header: Draft / Closed first, then
     * the CC decision, else waiting for faculty setup.
     *
     * @return array{0: string, 1: string} [label, badge class]
     */
    public function progress(): array
    {
        return match (true) {
            $this->status === self::STATUS_DRAFT => ['Draft', 'edc-status-draft'],
            $this->status === self::STATUS_CLOSED => ['Closed', 'edc-status-closed'],
            $this->cc_status === self::CC_APPROVED => [self::CC_STATUSES[self::CC_APPROVED], 'edc-status-active'],
            $this->cc_status === self::CC_REJECTED => [self::CC_STATUSES[self::CC_REJECTED], 'edc-status-closed'],
            $this->cc_status === self::CC_PENDING => [self::CC_STATUSES[self::CC_PENDING], 'edc-status-pending'],
            default => ['Pending at Faculty', 'edc-status-pending'],
        };
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? 'Draft';
    }
}
