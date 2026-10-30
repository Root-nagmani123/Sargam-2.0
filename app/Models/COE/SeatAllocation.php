<?php

namespace App\Models\COE;

use App\Models\StudentMaster;
use App\Models\VenueMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Where one participant sits for one examination.
 *
 * A row exists only once the participant has been seated — see the migration for why
 * "no row" is the unallocated state rather than a nullable seat number.
 */
class SeatAllocation extends Model
{
    protected $table = 'coe_seat_allocations';

    protected $primaryKey = 'pk';

    public $timestamps = false;

    /**
     * `is_manual` is absent on purpose: whether a seat was chosen by hand is decided by
     * WHICH action wrote it, not by what the request claimed.
     */
    protected $fillable = [
        'seat_plan_pk',
        'student_master_pk',
        'roll_number_pk',
        'venue_id',
        'seat_number',
        'requires_laptop',
        'requires_scriber',
        'allocated_at',
        'created_by',
        'created_date',
        'modified_by',
        'modified_date',
    ];

    protected $casts = [
        'seat_plan_pk' => 'integer',
        'student_master_pk' => 'integer',
        'roll_number_pk' => 'integer',
        'venue_id' => 'integer',
        'seat_number' => 'integer',
        'requires_laptop' => 'boolean',
        'requires_scriber' => 'boolean',
        'is_manual' => 'boolean',
        'allocated_at' => 'datetime',
        'created_date' => 'datetime',
        'modified_date' => 'datetime',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SeatPlan::class, 'seat_plan_pk', 'pk');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentMaster::class, 'student_master_pk', 'pk');
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(VenueMaster::class, 'venue_id', 'venue_id');
    }

    public function rollNumber(): BelongsTo
    {
        return $this->belongsTo(RollNumber::class, 'roll_number_pk', 'pk');
    }
}
