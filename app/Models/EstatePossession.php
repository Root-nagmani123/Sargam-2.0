<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstatePossession extends Model
{
    use HasFactory;

    protected $table = 'estate_possession';
    protected $primaryKey = 'pk';

    protected $fillable = [
        'estate_unit_master_pk',
        'employee_master_pk',
        'possession_date',
        'vacation_date',
        'meter_no_one',
        'meter_no_two',
        'initial_reading_one',
        'initial_reading_two',
        'licence_fee',
        'water_charge',
        'handover_date',
        'meter_number',
        'initial_meter_reading',
        'final_meter_reading',
        'possession_type',
        'remarks',
        'status',
        'created_by',
        'vacation_date' => 'date',
        'handover_date' => 'date',
        'initial_reading_one' => 'decimal:2',
        'initial_reading_two' => 'decimal:2',
        'licence_fee' => 'decimal:2',
        'water_charge' => 'decimal:2
        'modify_by',
        'modify_date',
    ];

    protected $casts = [
        'possession_date' => 'date',
        'handover_date' => 'date',
        'initial_meter_reading' => 'integer',
        'final_meter_reading' => 'integer',
        'created_date' => 'datetime',
        'modify_date' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_master_pk', 'id');
    }

    public function unit()
    {
        return $this->belongsTo(EstateUnitMaster::class, 'estate_unit_master_pk', 'pk');
    }

    public function meterReadings()
    {
        return $this->hasMany(EstateMeterReading::class, 'estate_possession_pk', 'pk');
    }

    public function billings()
    {
        return $this->hasMany(EstateBilling::class, 'estate_possession_pk', 'pk');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeVacated($query)
    {
        return $query->where('status', 'vacated');
    }
}
