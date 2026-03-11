<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KitchenIssueApproval extends Model
{
    use HasFactory;

    protected $table = 'kitchen_issue_approval';
    protected $primaryKey = 'pk';

    protected $fillable = [
        'kitchen_issue_master_pk',
        'approver_id',
        'approval_level',
        'status',
        'remarks',
        'approved_date',
    ];

    protected $casts = [
        'approved_date' => 'datetime',
    ];

    // Constants for approval status
    const STATUS_PENDING = 0;
    const STATUS_APPROVED = 1;
    const STATUS_REJECTED = 2;

    /**
     * Get the kitchen issue master
     */
    public function kitchenIssueMaster()
    {
        return $this->belongsTo(KitchenIssueMaster::class, 'kitchen_issue_master_pk', 'pk');
    }

    /**
     * Get the approver
     */
    public function approver()
    {
        return $this->belongsTo(Employee::class, 'approver_id', 'pk');
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute()
    {
        $labels = [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
        ];

        return $labels[$this->status] ?? 'Unknown';
    }

    /**
     * Scope for pending approvals
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for approved
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope for by approver
     */
    public function scopeByApprover($query, $approverId)
    {
        return $query->where('approver_id', $approverId);
    }

    /**
     * Scope for by level
     */
    public function scopeByLevel($query, $level)
    {
        return $query->where('approval_level', $level);
    }
}
