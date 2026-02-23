<?php

namespace App\Models\Mess;

use Illuminate\Database\Eloquent\Model;

/**
 * Selling Voucher with Date Range - standalone module (not related to Selling Voucher / Kitchen Issue).
 */
class SellingVoucherDateRangeReport extends Model
{
    protected $table = 'sv_date_range_reports';

    protected $fillable = [
        'date_from',
        'date_to',
        'store_id',
        'store_type',
        'report_title',
        'status',
        'total_amount',
        'remarks',
        'client_type_slug',
        'client_type_pk',
        'client_name',
        'payment_type',
        'issue_date',
        'created_by',
        'updated_by',
        'bill_path',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        'issue_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    const STATUS_DRAFT = 0;
    const STATUS_FINAL = 1;
    const STATUS_APPROVED = 2;

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id', 'id');
    }

    public function subStore()
    {
        return $this->belongsTo(SubStore::class, 'store_id', 'id');
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

    public function clientTypeCategory()
    {
        return $this->belongsTo(ClientType::class, 'client_type_pk', 'id');
    }

    /**
     * When client_type_slug is 'ot' or 'course', client_type_pk stores course_master.pk.
     */
    public function course()
    {
        return $this->belongsTo(\App\Models\CourseMaster::class, 'client_type_pk', 'pk');
    }

    /**
     * Display name for Client Name column: course name for OT/Course, else client type category name.
     */
    public function getDisplayClientNameAttribute()
    {
        if (in_array($this->client_type_slug, ['ot', 'course']) && $this->client_type_pk) {
            return $this->course?->course_name ?? '—';
        }
        return $this->clientTypeCategory?->client_name ?? '—';
    }

    public function items()
    {
        return $this->hasMany(SellingVoucherDateRangeReportItem::class, 'sv_date_range_report_id', 'id');
    }

    public function getGrandTotalAttribute()
    {
        return $this->items->sum('amount');
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_FINAL => 'Final',
            self::STATUS_APPROVED => 'Approved',
        ];
    }

    /**
     * Get status label for display
     */
    public function getStatusLabelAttribute(): string
    {
        return self::statusLabels()[$this->status] ?? 'Unknown';
    }

    /**
     * Get client type with category name for display, e.g. "Employee(ACADEMY STAFF)"
     */
    public function getClientTypeDisplayAttribute(): string
    {
        $typeLabel = $this->clientTypeCategory
            ? ucfirst($this->clientTypeCategory->client_type ?? '')
            : ucfirst($this->client_type_slug ?? '—');
        $categoryName = $this->clientTypeCategory?->client_name;
        if ($categoryName !== null && $categoryName !== '') {
            return $typeLabel . '(' . strtoupper($categoryName) . ')';
        }
        return $typeLabel ?: '—';
    }
}
