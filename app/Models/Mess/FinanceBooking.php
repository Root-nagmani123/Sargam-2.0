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
        'invoice_id',
        'user_id',
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
    
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
    
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'pk');
    }
    
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by', 'pk');
    }
}
