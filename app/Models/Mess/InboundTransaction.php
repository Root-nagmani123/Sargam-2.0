<?php

namespace App\Models\Mess;

use Illuminate\Database\Eloquent\Model;

class InboundTransaction extends Model
{
    protected $table = 'mess_inbound_transactions';
    
    protected $fillable = [
        'transaction_number', 'purchase_order_id', 'vendor_id', 'store_id',
        'receipt_date', 'invoice_number', 'invoice_amount', 'remarks', 'received_by'
    ];
    
    protected $casts = [
        'receipt_date' => 'date',
    ];
    
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }
    
    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }
    
    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }
    
    public function items()
    {
        return $this->hasMany(InboundTransactionItem::class, 'inbound_transaction_id');
    }
    
    public function receiver()
    {
        return $this->belongsTo(\App\Models\User::class, 'received_by');
    }
}
