<?php

namespace App\Models\COE;

use App\Models\VenueMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Which desks of one examination room are laptop desks and which are scriber desks.
 * Seat numbers run 1..venue_master.capacity, the same numbers coe_seat_allocations uses.
 */
class VenueSeatLayout extends Model
{
    protected $table = 'coe_venue_seat_layouts';

    protected $primaryKey = 'pk';

    public $timestamps = false;

    protected $fillable = [
        'venue_id',
        'laptop_seats',
        'scriber_seats',
        'created_by',
        'created_date',
        'modified_by',
        'modified_date',
    ];

    protected $casts = [
        'venue_id' => 'integer',
        'laptop_seats' => 'array',
        'scriber_seats' => 'array',
        'created_date' => 'datetime',
        'modified_date' => 'datetime',
    ];

    public function venue(): BelongsTo
    {
        return $this->belongsTo(VenueMaster::class, 'venue_id', 'venue_id');
    }

    /** @return list<int> */
    public function laptopSeatNumbers(): array
    {
        return self::normalise($this->laptop_seats);
    }

    /** @return list<int> */
    public function scriberSeatNumbers(): array
    {
        return self::normalise($this->scriber_seats);
    }

    /**
     * Distinct positive ints, ascending — what every reader of the json columns expects.
     *
     * @return list<int>
     */
    public static function normalise($seats): array
    {
        $seats = array_unique(array_filter(array_map('intval', (array) $seats), fn (int $n) => $n > 0));
        sort($seats);

        return array_values($seats);
    }
}
