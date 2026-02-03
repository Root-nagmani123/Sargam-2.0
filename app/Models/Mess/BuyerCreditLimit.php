<?php

namespace App\Models\Mess;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuyerCreditLimit extends Model
{
    use HasFactory;
    
    protected $table = 'mess_buyer_credit_limits';
    
    protected $fillable = [
        'buyer_id',
        'buyer_type',
        'max_limit',
        'used_amount',
        'available_limit'
    ];
    
    protected $casts = [
        'max_limit' => 'decimal:2',
        'used_amount' => 'decimal:2',
        'available_limit' => 'decimal:2'
    ];
    
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
     * Update credit limit usage
     */
    public function updateUsage($amount, $isPayment = false)
    {
        if ($isPayment) {
            $this->used_amount -= $amount;
        } else {
            $this->used_amount += $amount;
        }
        
        $this->available_limit = $this->max_limit - $this->used_amount;
        $this->save();
    }
}
