<?php

namespace App\Models\FC;

use Illuminate\Database\Eloquent\Model;

class StudentTravelPlanMaster extends Model
{
    protected $table = 'student_travel_plan_masters';

    protected $fillable = [
        'username', 'joining_date', 'joining_time',
        'special_requirements', 'is_submitted',
        'fc_travel_arrival_slot_id', 'mode_of_journey', 'journey_vehicle_no', 'academy_arrival_date',
        'arrival_time_dehradun', 'require_academy_vehicle',
    ];

    protected $casts = [
        'joining_date'    => 'date',
        'is_submitted'    => 'boolean',
        'academy_arrival_date'  => 'date',
        'require_academy_vehicle' => 'boolean',
    ];

    public function legs()
    {
        return $this->hasMany(MctpStudentTravelPlanDetail::class, 'travel_plan_id')
            ->orderByRaw('leg_number IS NULL, leg_number')
            ->orderBy('id');
    }

    public function fcArrivalSlot()
    {
        return $this->belongsTo(FcTravelArrivalSlot::class, 'fc_travel_arrival_slot_id');
    }

    /**
     * Normalize DB/driver values for the submitted flag (reports use raw query rows).
     */
    public static function interpretIsSubmitted(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return (int) $value === 1;
        }
        $s = strtolower(trim((string) $value));

        return in_array($s, ['1', 'true', 'yes', 'y', 'on'], true);
    }

    /**
     * Normalize DB/driver values (0/1, "1", tinyint, null) for UI and reports.
     */
    public static function interpretRequiresAcademyVehicle(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return (int) $value === 1;
        }
        $s = strtolower(trim((string) $value));

        return in_array($s, ['1', 'true', 'yes', 'y', 'on'], true);
    }

    public function requiresAcademyVehicleYes(): bool
    {
        return self::interpretRequiresAcademyVehicle($this->getRawOriginal('require_academy_vehicle'));
    }
}
