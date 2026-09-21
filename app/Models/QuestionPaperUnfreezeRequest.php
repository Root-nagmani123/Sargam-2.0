<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * COE Examination - a faculty request to reopen a frozen question paper.
 *
 * Freezing is meant to be final, so the only way back is an explicit request
 * with a stated reason that an authorised officer approves. Rejected requests
 * are kept as well: a refused attempt to reopen a locked paper is part of that
 * paper's history.
 */
class QuestionPaperUnfreezeRequest extends Model
{
    protected $table = 'question_paper_unfreeze_requests';
    protected $guarded = [];

    public $timestamps = false;

    protected $casts = [
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        // The status checks compare with ===, and the driver hands tinyint back
        // as a string; without the cast a pending request reads as decided.
        'status' => 'integer',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
    ];

    public const STATUS_PENDING = 0;
    public const STATUS_APPROVED = 1;
    public const STATUS_REJECTED = 2;

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_APPROVED => 'Approved',
        self::STATUS_REJECTED => 'Rejected',
    ];

    public const STATUS_BADGES = [
        self::STATUS_PENDING => 'warning',
        self::STATUS_APPROVED => 'success',
        self::STATUS_REJECTED => 'danger',
    ];

    public function questionPaper()
    {
        return $this->belongsTo(QuestionPaper::class, 'question_paper_id', 'id');
    }

    /** The Examination Section's inbox. */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeForDrive($query, $driveId)
    {
        return $query->whereHas('questionPaper', function ($q) use ($driveId) {
            $q->where('examination_drive_id', $driveId);
        });
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? 'Unknown';
    }

    public function statusBadge(): string
    {
        return self::STATUS_BADGES[$this->status] ?? 'secondary';
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
