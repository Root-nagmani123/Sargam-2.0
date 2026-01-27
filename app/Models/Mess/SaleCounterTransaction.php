<?php

namespace App\Models\Mess;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class SaleCounterTransaction extends Model
{
    use HasFactory;
    
    protected $table = 'mess_sale_counter_transactions';
    
    protected $fillable = [
        'transaction_number',
        'sale_counter_id',
        'user_id',
        'inventory_id',
        'quantity',
        'unit_price',
        'total_amount',
        'payment_mode',
        'transaction_date'
    ];
    
    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'transaction_date' => 'datetime'
    ];
    
    public function saleCounter()
    {
        return $this->belongsTo(SaleCounter::class);
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }
}
