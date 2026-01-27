<?php

namespace App\Models\Mess;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorItemMapping extends Model
{
    use HasFactory;
    
    protected $table = 'mess_vendor_item_mappings';
    
    protected $fillable = [
        'vendor_id',
        'inventory_id',
        'rate',
        'is_active'
    ];
    
    protected $casts = [
        'rate' => 'decimal:2',
        'is_active' => 'boolean'
    ];
    
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
    
    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }
}
