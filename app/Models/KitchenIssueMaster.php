<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Mess\Store;
use App\Models\Mess\Inventory;
use App\Models\User;

class KitchenIssueMaster extends Model
{
    use HasFactory;

    protected $table = 'kitchen_issue_master';
    protected $primaryKey = 'pk';

    protected $fillable = [
        'inve_item_master_pk',
        'inve_store_master_pk',
        'requested_store_id',
        'quantity',
        'user_id',
        'status',
        'store_employee_master_pk',
        'request_date',
        'unit_price',
        'payment_type',
        'issue_date',
        'transfer_to',
        'client_type',
        'client_type_pk',
        'client_name',
        'employee_student_pk',
        'bill_no',
        'send_for_approval',
        'notify_status',
        'approve_status',
        'paid_unpaid',
        'remarks',
        'created_by',
        'modified_by',
    ];

    protected $casts = [
        'request_date' => 'datetime',
        'issue_date' => 'date',
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
    ];

    // Constants for status
    const STATUS_PENDING = 0;
    const STATUS_PROCESSING = 1;
    const STATUS_APPROVED = 2;
    const STATUS_REJECTED = 3;
    const STATUS_COMPLETED = 4;

    // Constants for payment types
    const PAYMENT_CASH = 0;
    const PAYMENT_CREDIT = 1;
    const PAYMENT_DEBIT = 2;
    const PAYMENT_ACCOUNT = 5;

    // Constants for approval status
    const APPROVE_PENDING = 0;
    const APPROVE_APPROVED = 1;
    const APPROVE_REJECTED = 2;

    // Constants for paid/unpaid
    const UNPAID = 0;
    const PAID = 1;

    // Constants for client types
    const CLIENT_STUDENT = 2;
    const CLIENT_EMPLOYEE = 5;

    /**
     * Get the item master
     */
    public function itemMaster()
    {
        return $this->belongsTo(Inventory::class, 'inve_item_master_pk', 'id');
    }

    /**
     * Get the store master
     */
    public function storeMaster()
    {
        return $this->belongsTo(Store::class, 'inve_store_master_pk', 'id');
    }

    /**
     * Get the requested store
     */
    public function requestedStore()
    {
        return $this->belongsTo(Store::class, 'requested_store_id', 'id');
    }

    /**
     * Get the items for this kitchen issue
     */
    public function items()
    {
        return $this->hasMany(KitchenIssueItem::class, 'kitchen_issue_master_pk', 'pk');
    }

    /**
     * Get payment details
     */
    public function paymentDetails()
    {
        return $this->hasMany(KitchenIssuePaymentDetail::class, 'kitchen_issue_master_pk', 'pk');
    }

    /**
     * Get approval records
     */
    public function approvals()
    {
        return $this->hasMany(KitchenIssueApproval::class, 'kitchen_issue_master_pk', 'pk');
    }

    /**
     * Get the employee (if client_type is employee)
     */
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_student_pk', 'pk');
    }

    /**
     * Get the student (if client_type is student)
     */
    public function student()
    {
        return $this->belongsTo(User::class, 'employee_student_pk', 'pk');
    }

    /**
     * Get the creator
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the modifier
     */
    public function modifier()
    {
        return $this->belongsTo(User::class, 'modified_by');
    }

    /**
     * Get client type category (Client Name row from mess_client_types)
     */
    public function clientTypeCategory()
    {
        return $this->belongsTo(\App\Models\Mess\ClientType::class, 'client_type_pk', 'id');
    }

    /**
     * Grand total from items
     */
    public function getGrandTotalAttribute()
    {
        return $this->items->sum('amount');
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute()
    {
        $labels = [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_COMPLETED => 'Completed',
        ];

        return $labels[$this->status] ?? 'Unknown';
    }

    /**
     * Get payment type label
     */
    public function getPaymentTypeLabelAttribute()
    {
        $labels = [
            self::PAYMENT_CASH => 'Cash',
            self::PAYMENT_CREDIT => 'Credit',
            self::PAYMENT_DEBIT => 'Debit',
            self::PAYMENT_ACCOUNT => 'Account',
        ];

        return $labels[$this->payment_type] ?? 'Unknown';
    }

    /**
     * Get approve status label
     */
    public function getApproveStatusLabelAttribute()
    {
        $labels = [
            self::APPROVE_PENDING => 'Pending Approval',
            self::APPROVE_APPROVED => 'Approved',
            self::APPROVE_REJECTED => 'Rejected',
        ];

        return $labels[$this->approve_status] ?? 'Unknown';
    }

    /**
     * Get paid status label
     */
    public function getPaidStatusLabelAttribute()
    {
        return $this->paid_unpaid === self::PAID ? 'Paid' : 'Unpaid';
    }

    /**
     * Get total amount
     */
    public function getTotalAmountAttribute()
    {
        return $this->quantity * $this->unit_price;
    }

    /**
     * Get client name with type
     */
    public function getClientFullNameAttribute()
    {
        if ($this->client_type == self::CLIENT_EMPLOYEE && $this->employee) {
            return $this->employee->first_name . ' ' . $this->employee->last_name;
        } elseif ($this->client_type == self::CLIENT_STUDENT && $this->student) {
            return $this->student->first_name . ' ' . $this->student->last_name;
        }
        
        return $this->client_name ?? 'N/A';
    }

    /**
     * Scope for pending approvals
     */
    public function scopePendingApproval($query)
    {
        return $query->where('send_for_approval', 1)
                     ->where('approve_status', self::APPROVE_PENDING);
    }

    /**
     * Scope for approved records
     */
    public function scopeApproved($query)
    {
        return $query->where('approve_status', self::APPROVE_APPROVED);
    }

    /**
     * Scope for unpaid bills
     */
    public function scopeUnpaid($query)
    {
        return $query->where('paid_unpaid', self::UNPAID);
    }

    /**
     * Scope for by store
     */
    public function scopeByStore($query, $storeId)
    {
        return $query->where('inve_store_master_pk', $storeId);
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('request_date', [$startDate, $endDate]);
    }
}
