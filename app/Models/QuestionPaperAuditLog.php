<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * COE Examination - one recorded action on a question paper.
 *
 * Append-only: nothing in the application updates or deletes a row. A log that
 * can be edited is not an audit trail.
 */
class QuestionPaperAuditLog extends Model
{
    protected $table = 'question_paper_audit_logs';
    protected $guarded = [];

    public $timestamps = false;

    protected $casts = [
        'details' => 'array',
        'created_date' => 'datetime',
    ];

    public function questionPaper()
    {
        return $this->belongsTo(QuestionPaper::class, 'question_paper_id', 'id');
    }

    public function scopeForPaper($query, $paperId)
    {
        return $query->where('question_paper_id', $paperId);
    }

    public function scopeOfAction($query, string $action)
    {
        return $query->where('action', $action);
    }
}
