<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IssuePriorityMaster extends Model
{
    use HasFactory;

    protected $table = 'issue_priority_master';
    protected $primaryKey = 'pk';
    public $timestamps = false;

    protected $fillable = [
        'priority',
        'description',
        'priority_order',
        'created_by',
        'created_date',
        'modified_by',
        'modified_date',
        'status',
    ];

    protected $casts = [
        'created_date' => 'datetime',
        'modified_date' => 'datetime',
        'priority_order' => 'integer',
        'status' => 'integer',
    ];

    /**
     * Get the issue logs for this priority.
     */
    public function issueLogs()
    {
        return $this->hasMany(IssueLogManagement::class, 'issue_priority_master_pk', 'pk');
    }

    /**
     * Scope to get only active priorities.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope to order by priority.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('priority_order', 'asc');
    }
}
