<?php

namespace App\Models\Mess;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesTransactionItem extends Model
{
    use HasFactory;
    
    protected $table = 'mess_sales_transaction_items';
    
    protected $fillable = [
        'sale_transaction_id',
        'item_id',
        'quantity',
        'rate',
        'amount'
    ];
    
    protected $casts = [
        'quantity' => 'decimal:2',
        'rate' => 'decimal:2',
        'amount' => 'decimal:2'
    ];
    
    /**
     * Get the parent sale transaction
     */
    public function saleTransaction()
    {
        return $this->belongsTo(SalesTransaction::class, 'sale_transaction_id');
    }
    
    /**
     * Get the inventory item
     */
    public function item()
    {
        return $this->belongsTo(Inventory::class, 'item_id');
    }
}
