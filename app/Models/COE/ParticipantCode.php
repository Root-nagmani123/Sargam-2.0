<?php

namespace App\Models\COE;

use App\Models\StudentMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One participant's Unique Participant Code.
 *
 * Both `student_master_pk` and `unique_code` are unique across the whole table: one code
 * per participant, and no code shared between two participants.
 */
class ParticipantCode extends Model
{
    /** Generated but not yet frozen — the grid's "Pending" badge. */
    public const STATUS_PENDING = 1;

    /** Frozen; Edit and Delete are refused from here on. */
    public const STATUS_FROZEN = 2;

    protected $table = 'coe_participant_codes';

    protected $primaryKey = 'pk';

    public $timestamps = false;

    /**
     * status / frozen_at / frozen_by are deliberately absent: freezing is a workflow
     * transition owned by the controller, not a field a request may set.
     */
    protected $fillable = [
        'student_master_pk',
        'course_master_pk',
        'batch',
        'unique_code',
        'created_by',
        'created_date',
        'modified_by',
        'modified_date',
    ];

    protected $casts = [
        'student_master_pk' => 'integer',
        'course_master_pk' => 'integer',
        'batch' => 'integer',
        'status' => 'integer',
        'frozen_at' => 'datetime',
        'created_date' => 'datetime',
        'modified_date' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentMaster::class, 'student_master_pk', 'pk');
    }

    public function isFrozen(): bool
    {
        return (int) $this->status === self::STATUS_FROZEN;
    }

    public static function statusLabel(int $status): string
    {
        return $status === self::STATUS_FROZEN ? 'Frozen' : 'Pending';
    }
}
