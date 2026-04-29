<?php

namespace App\Models\FC;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FcTravelArrivalSlot extends Model
{
    protected $table = 'fc_travel_arrival_slots';

    protected $fillable = [
        'slot_date', 'slot_label', 'time_start', 'time_end', 'max_capacity', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'slot_date'     => 'date',
        'is_active'     => 'boolean',
        'max_capacity'  => 'integer',
        'sort_order'    => 'integer',
    ];

    public function travelPlans(): HasMany
    {
        return $this->hasMany(StudentTravelPlanMaster::class, 'fc_travel_arrival_slot_id');
    }

    public function countOtherBookings(string $username): int
    {
        return $this->travelPlans()
            ->where('username', '!=', $username)
            ->count();
    }

    public function hasRoomForUser(?string $username): bool
    {
        // null = no cap; 0 = closed/unavailable.
        if ($this->max_capacity === null) {
            return true;
        }
        if ((int) $this->max_capacity < 1) {
            return false;
        }
        $n = $username
            ? $this->countOtherBookings($username)
            : $this->travelPlans()->count();

        return $n < (int) $this->max_capacity;
    }
}
