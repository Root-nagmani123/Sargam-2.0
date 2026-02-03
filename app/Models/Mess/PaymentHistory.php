<?php

namespace App\Models\Mess;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class PaymentHistory extends Model
{
    use HasFactory;
    
    protected $table = 'mess_payment_history';
    
    protected $fillable = [
        'sale_transaction_id',
        'payment_amount',
        'payment_date',
        'payment_mode',
        'cheque_number',
        'reference_number',
        'remarks',
        'received_by'
    ];
    
    protected $casts = [
        'payment_amount' => 'decimal:2',
        'payment_date' => 'date'
    ];
    
    /**
     * Get the sale transaction
     */
    public function saleTransaction()
    {
        return $this->belongsTo(SalesTransaction::class, 'sale_transaction_id');
    }
    
    /**
     * Get the user who received the payment
     */
    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by', 'pk');
    }
}
