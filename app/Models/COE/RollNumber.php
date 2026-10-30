<?php

namespace App\Models\COE;

use App\Models\StudentMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One participant's roll number within an allocation run.
 *
 * `student_master_pk` is unique across the whole table — a participant sits one
 * course+batch and therefore holds one roll number — which is what lets the Roll Numbers
 * grid left-join on the participant alone instead of resolving the run per row.
 */
class RollNumber extends Model
{
    /** Generated but not yet frozen — the grid's "Pending" badge. */
    public const STATUS_GENERATED = 1;

    /** Frozen; Edit and Delete are refused from here on. */
    public const STATUS_FROZEN = 2;

    protected $table = 'coe_roll_numbers';

    protected $primaryKey = 'pk';

    public $timestamps = false;

    /**
     * status / frozen_at / frozen_by are deliberately absent: freezing is a workflow
     * transition owned by the controller, not a field a request may set.
     */
    protected $fillable = [
        'roll_number_allocation_pk',
        'student_master_pk',
        'serial_number',
        'roll_number',
        'created_by',
        'created_date',
        'modified_by',
        'modified_date',
    ];

    protected $casts = [
        'roll_number_allocation_pk' => 'integer',
        'student_master_pk' => 'integer',
        'serial_number' => 'integer',
        'status' => 'integer',
        'frozen_at' => 'datetime',
        'created_date' => 'datetime',
        'modified_date' => 'datetime',
    ];

    public function allocation(): BelongsTo
    {
        return $this->belongsTo(RollNumberAllocation::class, 'roll_number_allocation_pk', 'pk');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentMaster::class, 'student_master_pk', 'pk');
    }

    public function isFrozen(): bool
    {
        return (int) $this->status === self::STATUS_FROZEN;
    }
}
