<?php

namespace App\Models\Mess;

use Illuminate\Database\Eloquent\Model;

/**
 * Payment detail for Selling Voucher Date Range reports (process mess bills).
 */
class SvDateRangePaymentDetail extends Model
{
    protected $table = 'sv_date_range_payment_details';

    protected $fillable = [
        'sv_date_range_report_id',
        'paid_amount',
        'payment_date',
        'payment_mode',
        'bank_name',
        'cheque_number',
        'cheque_date',
        'remarks',
    ];

    protected $casts = [
        'paid_amount' => 'decimal:2',
        'payment_date' => 'date',
        'cheque_date' => 'date',
    ];

    public function report()
    {
        return $this->belongsTo(SellingVoucherDateRangeReport::class, 'sv_date_range_report_id', 'id');
    }
}
