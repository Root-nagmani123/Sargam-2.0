<?php

namespace App\Models\COE;

use Illuminate\Database\Eloquent\Model;

/**
 * One participant's marks in one component of a drive subject.
 *
 * Keyed on (examination_drive_id, subject_master_pk, component_master_pk,
 * student_master_pk) rather than the component map's id — see the migration
 * 2026_11_05_000001_create_coe_evaluation_marks_table for why.
 */
class EvaluationMark extends Model
{
    /** Saved by faculty, still editable. */
    public const STATUS_DRAFT = 1;

    /** Approved by faculty; locked, and counted by Result Compilation. */
    public const STATUS_APPROVED = 2;

    protected $table = 'coe_evaluation_marks';

    protected $primaryKey = 'pk';

    public $timestamps = false;

    /** status / approved_* are set by ResultMarksService::approve(), never from a request. */
    protected $fillable = [
        'examination_drive_id',
        'subject_master_pk',
        'component_master_pk',
        'student_master_pk',
        'marks',
        'is_absent',
        'evaluated_by_faculty_pk',
        'created_by',
        'created_date',
        'modified_by',
        'modified_date',
    ];

    protected $casts = [
        'marks' => 'float',
        'is_absent' => 'boolean',
        'status' => 'integer',
        'approved_at' => 'datetime',
    ];

    /** "drive:subject:component", the column key used across Result Management. */
    public function columnKey(): string
    {
        return $this->examination_drive_id.':'.$this->subject_master_pk.':'.$this->component_master_pk;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /** Filled in: a mark, or marked absent. */
    public function isFilled(): bool
    {
        return $this->is_absent || $this->marks !== null;
    }
}
