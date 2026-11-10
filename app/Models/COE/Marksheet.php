<?php

namespace App\Models\COE;

use Illuminate\Database\Eloquent\Model;

/**
 * One OT's marksheet on one course: Generated once they are ranked in Merit &
 * Ranking, Published by COE (MarksheetService). A published marksheet is read
 * from `snapshot`, never recomputed.
 */
class Marksheet extends Model
{
    public const STATUS_GENERATED = 1;

    public const STATUS_PUBLISHED = 2;

    protected $table = 'coe_marksheets';

    protected $primaryKey = 'pk';

    public $timestamps = false;

    /** status / published_* / snapshot are set by MarksheetService::publish(), never from a request. */
    protected $fillable = [
        'course_master_pk',
        'student_master_pk',
        'serial',
        'marksheet_number',
        'generated_at',
        'created_by',
        'created_date',
    ];

    protected $casts = [
        'course_master_pk' => 'integer',
        'student_master_pk' => 'integer',
        'serial' => 'integer',
        'status' => 'integer',
        'generated_at' => 'datetime',
        'published_at' => 'datetime',
        'snapshot' => 'array',
    ];

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }
}
