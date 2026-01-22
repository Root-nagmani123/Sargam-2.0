<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstateMeterReading extends Model
{
    use HasFactory;

    protected $table = 'estate_meter_reading';
    protected $primaryKey = 'pk';

    protected $fillable = [
        'estate_possession_pk',
        'meter_number',
        'reading_date',
        'meter_reading_one',
        'meter_reading_two',
        'units_consumed_one',
        'units_consumed_two',
        'electric_charge',
        'previous_reading',
        'current_reading',
        'units_consumed',
        'month',
        'year',
        'remarks',
        'created_by',
        'created_date',
        'reading_date' => 'date',
        'meter_reading_one' => 'decimal:2',
        'meter_reading_two' => 'decimal:2',
        'units_consumed_one' => 'decimal:2',
        'units_consumed_two' => 'decimal:2',
        'electric_charge' => 'decimal:2',
        'previous_reading' => 'integer',
        'current_reading' => 'integer',
        'units_consumed' => 'integer
    protected $casts = [
        'previous_reading' => 'integer',
        'current_reading' => 'integer',
        'units_consumed' => 'integer',
        'reading_date' => 'date',
        'month' => 'integer',
        'year' => 'integer',
        'created_date' => 'datetime',
        'modify_date' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function possession()
    {
        return $this->belongsTo(EstatePossession::class, 'estate_possession_pk', 'pk');
    }

    public function billing()
    {
        return $this->hasOne(EstateBilling::class, 'estate_meter_reading_pk', 'pk');
    }

    /**
     * Automatically calculate units consumed
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->units_consumed = $model->current_reading - $model->previous_reading;
        });
    }
}
