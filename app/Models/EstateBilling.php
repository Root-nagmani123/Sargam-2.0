<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstateBilling extends Model
{
    use HasFactory;

    protected $table = 'estate_billing';
    protected $primaryKey = 'pk';

    protected $fillable = [
        'estate_possession_pk',
        'estate_meter_reading_pk',
        'bill_number',
        'bill_date',
        'bill_month',
        'bill_year',
        'month',
        'year',
        'licence_fee',
        'water_charge',
        'electric_charge',
        'rent_amount',
        'electricity_amount',
        'water_amount',
        'other_charges',
        'total_amount',
        'paid_amount',
        'balance_amount',
        'payment_status',
        'payment_date',
        'due_date',
        'remarks',
        'created_by',
        'bill_year' => 'integer',
        'month' => 'integer',
        'year' => 'integer',
        'licence_fee' => 'decimal:2',
        'water_charge' => 'decimal:2',
        'electric_charge' => 'decimal:2',
        'rent_amount' => 'decimal:2',
        'electricity_amount' => 'decimal:2',
        'water_amount' => 'decimal:2',
        'other_charges' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balancedate' => 'date',
        'month' => 'integer',
        'year' => 'integer',
        'rent_amount' => 'decimal:2',
        'electricity_amount' => 'decimal:2',
        'water_amount' => 'decimal:2',
        'other_charges' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'payment_date' => 'date',
        'due_date' => 'date',
        'created_date' => 'datetime',
        'modify_date' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function possession()
    {
     

    public function payments()
    {
        return $this->hasMany(EstatePayment::class, 'estate_billing_pk', 'pk');
    }   return $this->belongsTo(EstatePossession::class, 'estate_possession_pk', 'pk');
    }

    public function meterReading()
    {
        return $this->belongsTo(EstateMeterReading::class, 'estate_meter_reading_pk', 'pk');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('payment_status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    /**
     * Automatically calculate total amount
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->total_amount = 
                $model->rent_amount + 
                $model->electricity_amount + 
                $model->water_amount + 
                $model->other_charges;
        });
    }
}
