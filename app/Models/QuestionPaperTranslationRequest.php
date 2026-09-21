<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * COE Examination - translation of a frozen question paper.
 *
 * Raised by the Examination Section once the faculty has frozen the original
 * and worked by the Translation Section. The translated file lands as a
 * QuestionPaperFile with the target language; nothing here touches the original.
 */
class QuestionPaperTranslationRequest extends Model
{
    protected $table = 'question_paper_translation_requests';
    protected $guarded = [];

    public $timestamps = false;

    protected $casts = [
        'requested_at' => 'datetime',
        'translated_at' => 'datetime',
        'frozen_at' => 'datetime',
        'approved_at' => 'datetime',
        // Compared with === in the guards below; the driver returns tinyint as
        // a string.
        'status' => 'integer',
    ];

    public const STATUS_PENDING = 0;
    public const STATUS_IN_PROGRESS = 1;
    public const STATUS_UPLOADED = 2;
    public const STATUS_FROZEN = 3;
    public const STATUS_APPROVED = 4;

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_IN_PROGRESS => 'In Progress',
        self::STATUS_UPLOADED => 'Uploaded',
        self::STATUS_FROZEN => 'Frozen',
        self::STATUS_APPROVED => 'Approved',
    ];

    public const STATUS_BADGES = [
        self::STATUS_PENDING => 'secondary',
        self::STATUS_IN_PROGRESS => 'warning',
        self::STATUS_UPLOADED => 'info',
        self::STATUS_FROZEN => 'primary',
        self::STATUS_APPROVED => 'success',
    ];

    public function questionPaper()
    {
        return $this->belongsTo(QuestionPaper::class, 'question_paper_id', 'id');
    }

    /** Everything still on the Translation Section's desk. */
    public function scopeOpen($query)
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING,
            self::STATUS_IN_PROGRESS,
            self::STATUS_UPLOADED,
        ]);
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? 'Unknown';
    }

    public function statusBadge(): string
    {
        return self::STATUS_BADGES[$this->status] ?? 'secondary';
    }

    /** The translated file may be replaced until the section freezes it. */
    public function isEditable(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_IN_PROGRESS,
            self::STATUS_UPLOADED,
        ], true);
    }

    public function canFreeze(): bool
    {
        return $this->status === self::STATUS_UPLOADED;
    }

    public function canApprove(): bool
    {
        return $this->status === self::STATUS_FROZEN;
    }

    public function targetLanguageLabel(): string
    {
        return config('coe.question_paper.languages')[$this->target_language] ?? $this->target_language;
    }
}
