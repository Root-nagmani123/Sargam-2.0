<?php

namespace App\Models\Mess;

use Illuminate\Database\Eloquent\Model;

/**
 * Item line for Selling Voucher with Date Range report (standalone).
 */
class SellingVoucherDateRangeReportItem extends Model
{
    protected $table = 'sv_date_range_report_items';

    protected $fillable = [
        'sv_date_range_report_id',
        'item_subcategory_id',
        'item_name',
        'unit',
        'available_quantity',
        'quantity',
        'return_quantity',
        'return_date',
        'rate',
        'amount',
    ];

    protected $casts = [
        'available_quantity' => 'decimal:2',
        'quantity' => 'decimal:2',
        'return_quantity' => 'decimal:2',
        'return_date' => 'date',
        'rate' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function report()
    {
        return $this->belongsTo(SellingVoucherDateRangeReport::class, 'sv_date_range_report_id', 'id');
    }

    public function itemSubcategory()
    {
        return $this->belongsTo(ItemSubcategory::class, 'item_subcategory_id', 'id');
    }

    protected static function boot()
    {
        parent::boot();
        static::saving(function ($item) {
            $item->amount = $item->quantity * $item->rate;
        });
    }
}
