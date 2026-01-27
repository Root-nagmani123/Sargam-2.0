<?php

namespace App\Models\Mess;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class FinanceBooking extends Model
{
    use HasFactory;
    
    protected $table = 'mess_finance_bookings';
    
    protected $fillable = [
        'booking_number',
        'inbound_transaction_id',
        'amount',
        'booking_date',
        'account_head',
        'status',
        'remarks',
        'approved_by',
        'approved_at'
    ];
    
    protected $casts = [
        'amount' => 'decimal:2',
        'booking_date' => 'date',
        'approved_at' => 'datetime'
    ];
    
    public function inboundTransaction()
    {
        return $this->belongsTo(InboundTransaction::class);
    }
    
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
