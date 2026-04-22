<?php

namespace App\Models\FC;

use Illuminate\Database\Eloquent\Model;

class MctpStudentTravelPlanDetail extends Model
{
    protected $table = 'mctp_student_travel_plan_details';

    protected $fillable = [
        'username', 'travel_plan_id', 'leg_number', 'leg_no',
        'from_station', 'to_station',
        'travel_mode', 'travel_mode_id',
        'travel_date', 'departure_time', 'arrival_time',
        'train_flight_no', 'train_flight_name', 'class_of_travel',
        'pnr_ticket_no', 'ticket_amount', 'is_entitled', 'remarks',
    ];

    protected $casts = [
        'travel_date'   => 'date',
        'ticket_amount' => 'decimal:2',
        'is_entitled'   => 'boolean',
    ];

    public function travelPlan()
    {
        return $this->belongsTo(StudentTravelPlanMaster::class, 'travel_plan_id');
    }

    public function travelMode()
    {
        return $this->belongsTo(MctpTravelModeMaster::class, 'travel_mode_id');
    }
}
