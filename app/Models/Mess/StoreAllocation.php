<?php

namespace App\Models\Mess;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreAllocation extends Model
{
    use HasFactory;

    protected $table = 'mess_store_allocations';

    protected $fillable = [
        'sub_store_id',
        'allocation_date',
        'store_name',
        'allocated_to',
        'quantity',
    ];

    protected $casts = [
        'allocation_date' => 'date',
    ];

    public function subStore()
    {
        return $this->belongsTo(SubStore::class, 'sub_store_id');
    }

    public function items()
    {
        return $this->hasMany(StoreAllocationItem::class, 'store_allocation_id');
    }
}
