<?php

namespace App\Models\Mess;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    protected $table = 'mess_purchase_orders';
    
    protected $fillable = [
        'po_number', 'vendor_id', 'store_id', 'po_date', 'delivery_date',
        'total_amount', 'status', 'remarks', 'created_by', 'approved_by', 'approved_at',
        'order_name', 'payment_code', 'delivery_address', 'contact_number'
    ];
    
    protected $casts = [
        'po_date' => 'date',
        'delivery_date' => 'date',
        'approved_at' => 'datetime',
    ];
    
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
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id');
    }
    
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
    
    public function approver()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }
}
