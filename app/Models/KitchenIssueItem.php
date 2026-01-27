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
        'item_name',
        'quantity',
        'rate',
        'amount',
        'unit',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
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
