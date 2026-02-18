<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Mess\Store;
use App\Models\Mess\SubStore;
use App\Models\Mess\Inventory;
use App\Models\User;

class KitchenIssueMaster extends Model
{
    use HasFactory;

    protected $table = 'kitchen_issue_master';
    protected $primaryKey = 'pk';

    protected $fillable = [
        'client_type',
        'payment_type',
        'client_id',
        'name_id',
        'issue_date',
        'store_id',
        'store_type',
        'kitchen_issue_type',
        'remarks',
        'status',
        'client_type_pk',
        'client_name',
    ];

    protected $casts = [
        'issue_date' => 'date',
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
    const PAYMENT_ONLINE = 2;

    // Constants for client types
    const CLIENT_EMPLOYEE = 1;
    const CLIENT_OT = 2;
    const CLIENT_COURSE = 3;
    const CLIENT_OTHER = 4;

    // Constants for kitchen issue types
    const TYPE_SELLING_VOUCHER = 1;
    const TYPE_SELLING_VOUCHER_DATE_RANGE = 2;

    /**
     * Get the store
     */
    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id', 'id');
    }

    /**
     * Get the sub-store
     */
    public function subStore()
    {
        return $this->belongsTo(SubStore::class, 'store_id', 'id');
    }

    /**
     * Alias for backward compatibility
     */
    public function storeMaster()
    {
        return $this->store();
    }

    /**
     * Get the resolved store name (works for both store and sub-store)
     */
    public function getResolvedStoreNameAttribute()
    {
        if ($this->store_type === 'sub_store') {
            $subStore = $this->subStore;
            return $subStore ? $subStore->sub_store_name . ' (Sub-Store)' : 'N/A';
        }
        $store = $this->store;
        return $store ? $store->store_name : 'N/A';
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
        return $this->belongsTo(\App\Models\EmployeeMaster::class, 'client_id', 'pk');
    }

    /**
     * Get the student (if client_type is OT/Course)
     */
    public function student()
    {
        return $this->belongsTo(\App\Models\StudentMaster::class, 'client_id', 'pk');
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
            self::PAYMENT_ONLINE => 'Online',
        ];

        return $labels[$this->payment_type] ?? 'Unknown';
    }

    /**
     * Get client type label
     */
    public function getClientTypeLabelAttribute()
    {
        $labels = [
            self::CLIENT_EMPLOYEE => 'Employee',
            self::CLIENT_OT => 'OT',
            self::CLIENT_COURSE => 'Course',
            self::CLIENT_OTHER => 'Other',
        ];

        return $labels[$this->client_type] ?? 'Unknown';
    }

    /**
     * Get kitchen issue type label
     */
    public function getKitchenIssueTypeLabelAttribute()
    {
        $labels = [
            self::TYPE_SELLING_VOUCHER => 'Selling Voucher',
            self::TYPE_SELLING_VOUCHER_DATE_RANGE => 'Selling Voucher with Date Range',
        ];

        return $labels[$this->kitchen_issue_type] ?? 'Unknown';
    }

    /**
     * Get client name with type
     */
    public function getClientFullNameAttribute()
    {
        if ($this->client_type == self::CLIENT_EMPLOYEE && $this->employee) {
            $emp = $this->employee;
            return trim(($emp->first_name ?? '') . ' ' . ($emp->middle_name ?? '') . ' ' . ($emp->last_name ?? ''));
        } elseif (in_array($this->client_type, [self::CLIENT_OT, self::CLIENT_COURSE]) && $this->student) {
            return $this->student->display_name ?? $this->student->first_name ?? '';
        }
        
        return $this->client_name ?? 'N/A';
    }

    /**
     * Scope for by store
     */
    public function scopeByStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('issue_date', [$startDate, $endDate]);
    }

    /**
     * Scope for by client type
     */
    public function scopeByClientType($query, $clientType)
    {
        return $query->where('client_type', $clientType);
    }

    /**
     * Scope for by kitchen issue type
     */
    public function scopeByKitchenIssueType($query, $type)
    {
        return $query->where('kitchen_issue_type', $type);
    }
}
