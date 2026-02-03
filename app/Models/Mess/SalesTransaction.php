<?php

namespace App\Models\Mess;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class SalesTransaction extends Model
{
    use HasFactory;
    
    protected $table = 'mess_sales_transactions';
    
    protected $fillable = [
        'bill_number',
        'store_id',
        'buyer_id',
        'buyer_type',
        'buyer_name',
        'sale_date',
        'total_amount',
        'paid_amount',
        'due_amount',
        'payment_mode',
        'payment_type',
        'paid_unpaid',
        'created_by'
    ];
    
    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'sale_date' => 'date'
    ];
    
    /**
     * Get the store associated with the sale
     */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }
    
    /**
     * Get the buyer (user) associated with the sale
     */
    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id', 'pk');
    }
    
    /**
     * Get the creator of the sale
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'pk');
    }
    
    /**
     * Get all items in this sale
     */
    public function items()
    {
        return $this->hasMany(SalesTransactionItem::class, 'sale_transaction_id');
    }
    
    /**
     * Get payment history for this sale
     */
    public function paymentHistory()
    {
        return $this->hasMany(PaymentHistory::class, 'sale_transaction_id');
    }
    
    /**
     * Get buyer type name
     */
    public function getBuyerTypeNameAttribute()
    {
        return match($this->buyer_type) {
            2 => 'OT',
            3 => 'Section',
            4 => 'Guest',
            5 => 'Employee',
            6 => 'Other',
            default => 'Unknown'
        };
    }
    
    /**
     * Get payment status
     */
    public function getPaymentStatusAttribute()
    {
        if ($this->due_amount == 0) {
            return 'Paid';
        } elseif ($this->paid_amount > 0 && $this->due_amount > 0) {
            return 'Partial';
        } else {
            return 'Unpaid';
        }
    }
}
