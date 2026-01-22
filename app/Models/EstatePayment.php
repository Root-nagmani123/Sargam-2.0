<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstatePayment extends Model
{
    use HasFactory;

    protected $table = 'estate_payment';
    protected $primaryKey = 'pk';

    protected $fillable = [
        'estate_billing_pk',
        'payment_date',
        'amount',
        'payment_mode',
        'transaction_reference',
        'cheque_number',
        'cheque_date',
        'bank_name',
        'remarks',
        'created_by',
        'created_date',
        'modify_by',
        'modify_date',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'cheque_date' => 'date',
        'amount' => 'decimal:2',
        'created_date' => 'datetime',
        'modify_date' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function billing()
    {
        return $this->belongsTo(EstateBilling::class, 'estate_billing_pk', 'pk');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
