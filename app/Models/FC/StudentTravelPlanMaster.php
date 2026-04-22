<?php

namespace App\Models\FC;

use Illuminate\Database\Eloquent\Model;

class StudentTravelPlanMaster extends Model
{
    protected $table = 'student_travel_plan_masters';

    protected $fillable = [
        'username', 'joining_date', 'joining_time', 'travel_type_id', 'travel_mode_id',
        'from_city', 'to_city', 'travel_date', 'train_flight_no', 'pickup_required', 'pickup_type_id',
        'needs_pickup', 'pickup_from_location', 'pickup_datetime',
        'needs_drop', 'drop_type_id', 'drop_to_location', 'drop_datetime',
        'departure_city', 'departure_state', 'special_requirements', 'is_submitted',
    ];

    protected $casts = [
        'joining_date'    => 'date',
        'travel_date'     => 'date',
        'pickup_datetime' => 'datetime',
        'drop_datetime'   => 'datetime',
        'needs_pickup'    => 'boolean',
        'needs_drop'      => 'boolean',
        'is_submitted'    => 'boolean',
    ];

    public function travelType()
    {
        return $this->belongsTo(TravelTypeMaster::class, 'travel_type_id');
    }

    public function travelMode()
    {
        return $this->belongsTo(MctpTravelModeMaster::class, 'travel_mode_id');
    }

    public function pickupType()
    {
        return $this->belongsTo(PickUpDropTypeMaster::class, 'pickup_type_id');
    }

    public function dropType()
    {
        return $this->belongsTo(PickUpDropTypeMaster::class, 'drop_type_id');
    }

    public function legs()
    {
        return $this->hasMany(MctpStudentTravelPlanDetail::class, 'travel_plan_id')
            ->orderByRaw('leg_number IS NULL, leg_number')
            ->orderBy('id');
    }
}
