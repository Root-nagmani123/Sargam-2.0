<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One entry of COE › Question Paper Management Logs. Written only by
 * App\Services\COE\QuestionPaperLogger; never changed afterwards.
 */
class QuestionPaperLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'question_paper_id',
        'user_id',
        'user_name',
        'role',
        'module',
        'action',
        'detail',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /** Deleted papers too: their history stays readable. */
    public function paper(): BelongsTo
    {
        return $this->belongsTo(QuestionPaper::class, 'question_paper_id')->withTrashed();
    }
}
