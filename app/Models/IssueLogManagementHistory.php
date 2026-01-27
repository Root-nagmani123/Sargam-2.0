<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IssueLogManagementHistory extends Model
{
    use HasFactory;

    protected $table = 'issue_log_management_history';
    protected $primaryKey = 'pk';
    public $timestamps = false;

    protected $fillable = [
        'issue_log_management_pk',
        'escalated_about',
        'escalated_to_employee_pk',
        'status',
        'assign_date',
        'employee_pk_assign1',
        'priority',
        'notify_level_1',
        'notify_level_2',
        'notify_level_3',
        'notify_datetime',
    ];

    protected $casts = [
        'assign_date' => 'datetime',
        'notify_datetime' => 'datetime',
        'status' => 'integer',
        'priority' => 'integer',
    ];

    /**
     * Get the issue log.
     */
    public function issueLog()
    {
        return $this->belongsTo(IssueLogManagement::class, 'issue_log_management_pk', 'pk');
    }
}
