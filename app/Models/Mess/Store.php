<?php

namespace App\Models\Mess;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    protected $table = 'mess_stores';
    
    protected $fillable = [
        'store_name', 'store_code', 'location', 'incharge_name',
        'incharge_contact', 'status'
    ];
    
    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class, 'store_id');
    }
    
    public function materialRequests()
    {
        return $this->hasMany(MaterialRequest::class, 'store_id');
    }
    
    public function inboundTransactions()
    {
        return $this->hasMany(InboundTransaction::class, 'store_id');
    }
}
