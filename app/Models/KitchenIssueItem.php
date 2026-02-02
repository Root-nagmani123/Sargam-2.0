<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KitchenIssueItem extends Model
{
    use HasFactory;

    protected $table = 'kitchen_issue_items';
    protected $primaryKey = 'pk';

    protected $fillable = [
        'kitchen_issue_master_pk',
        'item_subcategory_id',
        'item_name',
        'quantity',
        'available_quantity',
        'return_quantity',
        'rate',
        'amount',
        'unit',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'available_quantity' => 'decimal:2',
        'return_quantity' => 'decimal:2',
        'rate' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    /**
     * Get the kitchen issue master
     */
    public function kitchenIssueMaster()
    {
        return $this->belongsTo(KitchenIssueMaster::class, 'kitchen_issue_master_pk', 'pk');
    }

    /**
     * Get the item subcategory
     */
    public function itemSubcategory()
    {
        return $this->belongsTo(\App\Models\Mess\ItemSubcategory::class, 'item_subcategory_id', 'id');
    }

    /**
     * Left quantity (available - issue quantity)
     */
    public function getLeftQuantityAttribute()
    {
        $avail = (float) ($this->available_quantity ?? 0);
        $issue = (float) ($this->quantity ?? 0);
        return max(0, $avail - $issue);
    }

    /**
     * Calculate amount automatically
     */
    public function calculateAmount()
    {
        $this->amount = $this->quantity * $this->rate;
        return $this->amount;
    }

    /**
     * Boot method to auto-calculate amount
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->amount = $item->quantity * $item->rate;
        });
    }
}
