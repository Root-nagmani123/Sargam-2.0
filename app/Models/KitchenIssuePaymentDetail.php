<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KitchenIssuePaymentDetail extends Model
{
    use HasFactory;

    protected $table = 'kitchen_issue_payment_details';
    protected $primaryKey = 'pk';

    protected $fillable = [
        'kitchen_issue_master_pk',
        'invoice_no',
        'paid_amount',
        'payment_date',
        'payment_mode',
        'transaction_ref',
        'remarks',
    ];

    protected $casts = [
        'paid_amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    // Constants for payment modes
    const MODE_CASH = 0;
    const MODE_ONLINE = 1;
    const MODE_CHEQUE = 2;

    /**
     * Get the kitchen issue master
     */
    public function kitchenIssueMaster()
    {
        return $this->belongsTo(KitchenIssueMaster::class, 'kitchen_issue_master_pk', 'pk');
    }

    /**
     * Get payment mode label
     */
    public function getPaymentModeLabelAttribute()
    {
        $labels = [
            self::MODE_CASH => 'Cash',
            self::MODE_ONLINE => 'Online',
            self::MODE_CHEQUE => 'Cheque',
        ];

        return $labels[$this->payment_mode] ?? 'Unknown';
    }
}
