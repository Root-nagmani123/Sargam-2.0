<?php

namespace App\Models;

use App\Support\COE\QuestionPaperStatus;
use Illuminate\Database\Eloquent\Model;

/**
 * COE Examination - a snapshot of a question paper at each lock point.
 *
 * Cut on every freeze, unfreeze and finalize. The audit log says an action
 * happened; this says which files the paper held when it did, so a paper that
 * was later corrected can still show what was frozen the first time.
 */
class QuestionPaperVersion extends Model
{
    protected $table = 'question_paper_versions';
    protected $guarded = [];

    public $timestamps = false;

    protected $casts = [
        'file_snapshot' => 'array',
        'created_date' => 'datetime',
        'status_at_version' => 'integer',
        'version_no' => 'integer',
    ];

    public function questionPaper()
    {
        return $this->belongsTo(QuestionPaper::class, 'question_paper_id', 'id');
    }

    public function statusLabel(): string
    {
        return QuestionPaperStatus::label($this->status_at_version);
    }

    /** Version numbers run 1, 2, 3... per paper. */
    public static function nextVersionNo($questionPaperId): int
    {
        return (int) static::where('question_paper_id', $questionPaperId)->max('version_no') + 1;
    }
}
