<?php

namespace App\Models\Mess;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    protected $table = 'mess_purchase_order_items';
    
    protected $fillable = [
        'purchase_order_id', 'inventory_id', 'quantity', 'unit',
        'unit_price', 'total_price', 'description'
    ];
    
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }
    
    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }
}
