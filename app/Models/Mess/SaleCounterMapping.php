<?php

namespace App\Models\Mess;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleCounterMapping extends Model
{
    use HasFactory;
    
    protected $table = 'mess_sale_counter_mappings';
    
    protected $fillable = [
        'sale_counter_id',
        'inventory_id',
        'available_quantity',
        'is_active'
    ];
    
    protected $casts = [
        'is_active' => 'boolean'
    ];
    
    public function saleCounter()
    {
        return $this->belongsTo(SaleCounter::class);
    }
    
    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }
}
