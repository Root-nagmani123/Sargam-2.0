<?php

namespace App\Models\COE;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One OT's re-exam marksheet (Pass / Fail only): Generated once their re-exam result is
 * in, signed UDC → AAO → COE, Published by the COE (ReexamMarksheetService). A published
 * one is read from `snapshot`, never recomputed. The main marksheet is Marksheet.
 */
class ReexamMarksheet extends Model
{
    public const STATUS_GENERATED = 1;

    public const STATUS_PUBLISHED = 2;

    protected $table = 'coe_reexam_marksheets';

    protected $primaryKey = 'pk';

    public $timestamps = false;

    /** status / signed / published_* / snapshot are set by ReexamMarksheetService::verify(), never from a request. */
    protected $fillable = [
        'reexam_id',
        'course_master_pk',
        'student_master_pk',
        'serial',
        'marksheet_number',
        'generated_at',
        'created_by',
        'created_date',
    ];

    protected $casts = [
        'reexam_id' => 'integer',
        'course_master_pk' => 'integer',
        'student_master_pk' => 'integer',
        'serial' => 'integer',
        'status' => 'integer',
        'generated_at' => 'datetime',
        'published_at' => 'datetime',
        'snapshot' => 'array',
        'signed' => 'array',
    ];

    public function reexam(): BelongsTo
    {
        return $this->belongsTo(Reexam::class, 'reexam_id');
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }
}
