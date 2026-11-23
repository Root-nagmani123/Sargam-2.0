<?php

namespace App\Models\COE;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One OT sitting one subject of a re-exam, and why (Failed / Absent). */
class ReexamCandidate extends Model
{
    public const UPDATED_AT = null;

    public const REASON_FAILED = 1;
    public const REASON_ABSENT = 2;

    public const REASONS = [
        self::REASON_FAILED => 'Failed',
        self::REASON_ABSENT => 'Absent',
    ];

    protected $table = 'coe_reexam_candidates';

    protected $guarded = [];

    protected $casts = [
        'reexam_id' => 'integer',
        'subject_master_pk' => 'integer',
        'student_master_pk' => 'integer',
        'reason' => 'integer',
        'score_percent' => 'float',
        'created_at' => 'datetime',
    ];

    public function reexam(): BelongsTo
    {
        return $this->belongsTo(Reexam::class, 'reexam_id');
    }
}
