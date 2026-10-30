<?php

namespace App\Models\COE;

use App\Models\VenueMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One room a seating plan may use, and what it is being used for in THIS examination.
 *
 * See the migration for why the selection is stored rather than derived from the seats.
 */
class SeatPlanVenue extends Model
{
    /** Only participants who need a laptop-capable desk go here. */
    public const PURPOSE_LAPTOP = 1;

    /** Only participants who do not. */
    public const PURPOSE_NON_LAPTOP = 2;

    /** Either — the room takes whoever is next. */
    public const PURPOSE_ANY = 3;

    protected $table = 'coe_seat_plan_venues';

    protected $primaryKey = 'pk';

    public $timestamps = false;

    protected $fillable = [
        'seat_plan_pk',
        'venue_id',
        'purpose',
        'capacity_override',
        'display_order',
        'created_by',
        'created_date',
    ];

    protected $casts = [
        'seat_plan_pk' => 'integer',
        'venue_id' => 'integer',
        'purpose' => 'integer',
        'capacity_override' => 'integer',
        'display_order' => 'integer',
        'created_date' => 'datetime',
    ];

    /**
     * @return array<int, string>
     */
    public static function purposes(): array
    {
        return [
            self::PURPOSE_ANY => 'Either',
            self::PURPOSE_LAPTOP => 'Laptop seats only',
            self::PURPOSE_NON_LAPTOP => 'Non-laptop seats only',
        ];
    }

    public static function purposeLabel(?int $purpose): string
    {
        return self::purposes()[$purpose] ?? self::purposes()[self::PURPOSE_ANY];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SeatPlan::class, 'seat_plan_pk', 'pk');
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(VenueMaster::class, 'venue_id', 'venue_id');
    }

    /**
     * How many participants this room may take in this examination.
     *
     * The three sources are tried in the order they are authoritative: the override the
     * administrator typed for today, then the master's figure for the KIND of seat this
     * room is being used for, then its plain capacity.
     *
     * The laptop / seating split matters: a room with capacity 30 but laptop_capacity 10
     * seats thirty people for a written paper and ten for an online one, and using the
     * wrong one is how twenty candidates arrive to find no desk. Returns 0 rather than
     * null when nothing is configured, so an unconfigured room is skipped loudly by the
     * allocator's shortfall report instead of quietly swallowing everybody.
     *
     * @param  object|null  $venue  a venue_master row; capacity columns are read off it
     */
    public function effectiveCapacity(?object $venue): int
    {
        if ($this->capacity_override !== null) {
            return max(0, (int) $this->capacity_override);
        }

        if ($venue === null) {
            return 0;
        }

        $capacity = (int) ($venue->capacity ?? 0);

        $specific = match ((int) $this->purpose) {
            self::PURPOSE_LAPTOP => $venue->laptop_capacity ?? null,
            self::PURPOSE_NON_LAPTOP => $venue->seating_capacity ?? null,
            // "Either" cannot prefer one of the two figures without guessing which half
            // of the cohort turns up, so it uses the room's total.
            default => null,
        };

        return max(0, (int) ($specific ?? $capacity));
    }

    /** Can this room take a participant with this laptop requirement? */
    public function accepts(bool $requiresLaptop): bool
    {
        return match ((int) $this->purpose) {
            self::PURPOSE_LAPTOP => $requiresLaptop,
            self::PURPOSE_NON_LAPTOP => ! $requiresLaptop,
            default => true,
        };
    }
}
