<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One subject/component mark scheme row for an examination drive. */
class ExaminationDriveComponentMap extends Model
{
    protected $table = 'examination_drive_component_maps';

    protected $guarded = [];

    public $timestamps = false;

    protected $casts = [
        'sub_faculty_names' => 'array',
        'faculty_codes' => 'array',
        'faculty_unique_codes' => 'array',
        // Tentative Exam Range: dates, kept in step with the times below.
        'exam_date' => 'date',
        'exam_end_date' => 'date',
        // Tentative Exam Range with times (date + time); when set, it decides when the exam is over.
        'exam_start_at' => 'datetime',
        'exam_end_at' => 'datetime',
        // The Course Coordinator's confirmed slot, set on approval.
        'proposed_start_at' => 'datetime',
        'proposed_end_at' => 'datetime',
    ];

    /** The slot that counts: the CC's confirmed one once set, else the tentative one (may be null). */
    public function slotStart(): ?\Carbon\Carbon
    {
        return $this->proposed_start_at ?? $this->exam_start_at;
    }

    public function slotEnd(): ?\Carbon\Carbon
    {
        return $this->proposed_end_at ?? $this->exam_end_at;
    }

    public function drive(): BelongsTo
    {
        return $this->belongsTo(ExaminationDrive::class, 'examination_drive_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(SubjectMaster::class, 'subject_master_pk', 'pk');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(ComponentMaster::class, 'component_master_pk', 'pk');
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(FacultyMaster::class, 'faculty_master_pk', 'pk');
    }

    /**
     * Sub-coordinators for this component: the faculty set during faculty
     * Complete Setup (faculty_master_pk) plus any further names
     * (sub_faculty_names). Trimmed and de-duplicated, setup faculty first.
     *
     * @return string[]
     */
    public function subCoordinatorNames(): array
    {
        return collect([$this->faculty->full_name ?? null])
            ->merge($this->sub_faculty_names ?? [])
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
