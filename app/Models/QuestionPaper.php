<?php

namespace App\Models;

use App\Support\COE\QuestionPaperStatus;
use Illuminate\Database\Eloquent\Model;

/**
 * COE Examination - one question paper per written component of a drive.
 *
 * Sits on top of the Examination Drive module: the drive decides which subjects
 * and components exist, this decides which of them need a paper written and
 * tracks that paper from assignment to finalization.
 */
class QuestionPaper extends Model
{
    protected $table = 'question_papers';
    protected $guarded = [];

    public $timestamps = false;

    protected $casts = [
        'deadline' => 'date',
        'frozen_at' => 'datetime',
        'finalized_at' => 'datetime',
        // Every guard compares the status with ===, and the driver hands
        // tinyint back as a string; without the cast a frozen paper would read
        // as editable.
        'status' => 'integer',
    ];

    protected $attributes = [
        'status' => QuestionPaperStatus::DRAFT,
    ];

    /* -------------------------------------------------- relations */

    public function drive()
    {
        return $this->belongsTo(ExaminationDrive::class, 'examination_drive_id', 'id');
    }

    /** The subject + component this paper is for, with its marks and source. */
    public function componentMap()
    {
        return $this->belongsTo(ExaminationDriveComponentMap::class, 'drive_component_map_id', 'id');
    }

    public function faculty()
    {
        return $this->belongsTo(FacultyMaster::class, 'faculty_master_pk', 'pk');
    }

    public function files()
    {
        return $this->hasMany(QuestionPaperFile::class, 'question_paper_id', 'id');
    }

    /** Files that have not been superseded by a later upload. */
    public function currentFiles()
    {
        return $this->hasMany(QuestionPaperFile::class, 'question_paper_id', 'id')
            ->where('is_current', 1);
    }

    public function versions()
    {
        return $this->hasMany(QuestionPaperVersion::class, 'question_paper_id', 'id')
            ->orderByDesc('version_no');
    }

    public function translationRequests()
    {
        return $this->hasMany(QuestionPaperTranslationRequest::class, 'question_paper_id', 'id');
    }

    public function unfreezeRequests()
    {
        return $this->hasMany(QuestionPaperUnfreezeRequest::class, 'question_paper_id', 'id')
            ->orderByDesc('requested_at');
    }

    /** The unfreeze request still awaiting a decision, if any. */
    public function pendingUnfreezeRequest()
    {
        return $this->hasOne(QuestionPaperUnfreezeRequest::class, 'question_paper_id', 'id')
            ->where('status', QuestionPaperUnfreezeRequest::STATUS_PENDING);
    }

    /* -------------------------------------------------- scopes */

    public function scopeForDrive($query, $driveId)
    {
        return $query->where('examination_drive_id', $driveId);
    }

    /** A faculty member sees only the papers assigned to them. */
    public function scopeForFaculty($query, $facultyPk)
    {
        return $query->where('faculty_master_pk', $facultyPk);
    }

    /* -------------------------------------------------- state */

    public function statusLabel(): string
    {
        return QuestionPaperStatus::label($this->status);
    }

    public function statusBadge(): string
    {
        return QuestionPaperStatus::badge($this->status);
    }

    public function isEditable(): bool
    {
        return QuestionPaperStatus::isEditable($this->status);
    }

    public function canFreeze(): bool
    {
        // Freezing an empty paper would lock in nothing, so the files have to
        // be there before the status can move.
        return QuestionPaperStatus::canFreeze($this->status)
            && $this->currentFiles()->exists();
    }

    /** Only one unfreeze request may be open at a time. */
    public function canRequestUnfreeze(): bool
    {
        return QuestionPaperStatus::canRequestUnfreeze($this->status)
            && ! $this->pendingUnfreezeRequest()->exists();
    }

    public function canMarkForTranslation(): bool
    {
        return QuestionPaperStatus::canMarkForTranslation($this->status);
    }

    public function canFinalize(): bool
    {
        return QuestionPaperStatus::canFinalize($this->status);
    }

    public function isLocked(): bool
    {
        return QuestionPaperStatus::isLocked($this->status);
    }

    /* -------------------------------------------------- reporting */

    /**
     * Progress figures for one drive, for the Examination Drive dashboard's
     * "Question Paper" step. Exposed here so that module reads one number from
     * this one rather than growing its own copy of the same counting rules.
     */
    public static function driveProgress($driveId): array
    {
        $counts = static::forDrive($driveId)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $total = (int) $counts->sum();
        $finalized = (int) $counts->get(QuestionPaperStatus::FINALIZED, 0);

        return [
            'total' => $total,
            'finalized' => $finalized,
            'pending' => $total - $finalized,
            // An empty drive has nothing to finish, so it is not "complete".
            'is_complete' => $total > 0 && $finalized === $total,
        ];
    }
}
