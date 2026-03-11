<?php

namespace App\Models\Mess;

use Illuminate\Database\Eloquent\Model;

class StoreAllocationItem extends Model
{
    protected $table = 'mess_store_allocation_items';

    protected $fillable = [
        'store_allocation_id',
        'item_subcategory_id',
        'quantity',
        'unit',
        'unit_price',
        'total_price',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function storeAllocation()
    {
        return $this->belongsTo(StoreAllocation::class, 'store_allocation_id');
    }

    public function itemSubcategory()
    {
        return $this->belongsTo(ItemSubcategory::class, 'item_subcategory_id');
    }
}
