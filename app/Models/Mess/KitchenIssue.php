<?php

namespace App\Models\Mess;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class KitchenIssue extends Model
{
    use HasFactory;
    
    protected $table = 'mess_kitchen_issues';
    
    protected $fillable = [
        'bill_no',
        'buyer_id',
        'buyer_name',
        'guest_name',
        'issue_date',
        'total_amount',
        'client_type',
        'section',
        'programme_name',
        'payment_type',
        'status',
        'is_deleted',
        'created_by',
        'remarks'
    ];

    protected $casts = [
        'issue_date' => 'date',
        'total_amount' => 'decimal:2',
        'is_deleted' => 'boolean',
    ];

    /**
     * Relationship with buyer (user)
     */
    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    /**
     * Relationship with creator
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope for active kitchen issues
     */
    public function scopeActive($query)
    {
        return $query->where('is_deleted', 0);
    }

    /**
     * Scope for guest type
     */
    public function scopeGuest($query)
    {
        return $query->where('client_type', 'guest');
    }

    /**
     * Scope for employee type
     */
    public function scopeEmployee($query)
    {
        return $query->where('client_type', '!=', 'guest');
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('issue_date', [$startDate, $endDate]);
    }
}
