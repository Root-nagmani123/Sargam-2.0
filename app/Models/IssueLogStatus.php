<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IssueLogStatus extends Model
{
    use HasFactory;

    protected $table = 'issue_log_status';
    protected $primaryKey = 'pk';
    public $timestamps = false;

    protected $fillable = [
        'issue_log_management_pk',
        'issue_date',
        'created_by',
        'issue_status',
        'remarks',
        'assign_to',
    ];

    protected $casts = [
        'issue_date' => 'datetime',
        'issue_status' => 'integer',
    ];

    /**
     * Get the issue log.
     */
    public function issueLog()
    {
        return $this->belongsTo(IssueLogManagement::class, 'issue_log_management_pk', 'pk');
    }

    /**
     * Get the user who created this status.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'pk');
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute()
    {
        return match($this->issue_status) {
            0 => 'Reported',
            1 => 'In Progress',
            2 => 'Completed',
            3 => 'Pending',
            6 => 'Reopened',
            default => 'Unknown',
        };
    }
}
