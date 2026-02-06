<?php

namespace App\Models\Mess;

use Illuminate\Database\Eloquent\Model;

class InboundTransactionItem extends Model
{
    protected $table = 'mess_inbound_transaction_items';
    
    protected $fillable = [
        'inbound_transaction_id', 'inventory_id', 'quantity', 'unit',
        'unit_price', 'total_price', 'remarks'
    ];
    
    public function inboundTransaction()
    {
        return $this->belongsTo(InboundTransaction::class, 'inbound_transaction_id');
    }
    
    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }
}
