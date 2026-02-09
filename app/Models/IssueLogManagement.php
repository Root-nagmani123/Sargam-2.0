<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IssueLogManagement extends Model
{
    use HasFactory;

    protected $table = 'issue_log_management';
    protected $primaryKey = 'pk';
    public $timestamps = false;

    // Issue status constants
    const STATUS_REPORTED = 0;
    const STATUS_IN_PROGRESS = 1;
    const STATUS_COMPLETED = 2;
    const STATUS_PENDING = 3;
    const STATUS_REOPENED = 6;

    // Behalf constants
    const BEHALF_CENTCOM = 0; // On behalf (Centcom)
    const BEHALF_SELF = 1; // MySelf

    protected $fillable = [
        'issue_category_master_pk',
        'issue_priority_master_pk',
        'issue_reproducibility_master_pk',
        'description',
        'location',
        'document',
        'issue_status',
        'remark',
        'created_by',
        'created_date',
        'created_time',
        'issue_logger',
        'behalf',
        'employee_master_pk',
        'assigned_to',
        'assigned_to_contact',
        'notification_status',
        'feedback',
        'feedback_status',
        'latitude',
        'longitude',
        'image_name',
        'device_type',
        'device_id',
        'updated_by',
        'updated_date',
        'clear_date',
        'clear_time',
    ];

    protected $casts = [
        'created_date' => 'datetime',
        'updated_date' => 'datetime',
        'clear_date' => 'datetime',
        'issue_status' => 'integer',
        'behalf' => 'integer',
        'notification_status' => 'integer',
        'feedback_status' => 'integer',
    ];

    /**
     * Get the category of this issue.
     */
    public function category()
    {
        return $this->belongsTo(IssueCategoryMaster::class, 'issue_category_master_pk', 'pk');
    }

    /**
     * Get the priority of this issue.
     */
    public function priority()
    {
        return $this->belongsTo(IssuePriorityMaster::class, 'issue_priority_master_pk', 'pk');
    }

    /**
     * Get the reproducibility of this issue.
     */
    public function reproducibility()
    {
        return $this->belongsTo(IssueReproducibilityMaster::class, 'issue_reproducibility_master_pk', 'pk');
    }

    /**
     * Get the sub-category mappings for this issue.
     */
    public function subCategoryMappings()
    {
        return $this->hasMany(IssueLogSubCategoryMap::class, 'issue_log_management_pk', 'pk');
    }

    /**
     * Get the building mapping for this issue.
     */
    public function buildingMapping()
    {
        return $this->hasOne(IssueLogBuildingMap::class, 'issue_log_management_pk', 'pk');
    }

    /**
     * Get the hostel mapping for this issue.
     */
    public function hostelMapping()
    {
        return $this->hasOne(IssueLogHostelMap::class, 'issue_log_management_pk', 'pk');
    }

    /**
     * Get the status history for this issue (latest/last record first).
     */
    public function statusHistory()
    {
        return $this->hasMany(IssueLogStatus::class, 'issue_log_management_pk', 'pk')
                    
                    ->orderBy('issue_date', 'desc');
    }

    /**
     * Get the escalation history for this issue.
     */
    public function escalationHistory()
    {
        return $this->hasMany(IssueLogManagementHistory::class, 'issue_log_management_pk', 'pk');
    }

    /**
     * Get the user who created this issue.
     */
    public function creator()
    {
        return $this->belongsTo(EmployeeMaster::class, 'created_by', 'pk');
    }

     public function nodal_officer()
    {
        return $this->belongsTo(EmployeeMaster::class, 'employee_master_pk', 'pk');
    }

    /**
     * Get the employee who logged this issue (the one who submitted the form).
     */
    public function logger()
    {
        return $this->belongsTo(EmployeeMaster::class, 'issue_logger', 'pk');
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('issue_status', $status);
    }

    /**
     * Scope to get reported issues (Centcom).
     */
    public function scopeReportedOnBehalf($query)
    {
        return $query->where('behalf', self::BEHALF_CENTCOM);
    }

    /**
     * Scope to get self-reported issues.
     */
    public function scopeSelfReported($query)
    {
        return $query->where('behalf', self::BEHALF_SELF);
    }

    /**
     * Scope to filter by category.
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('issue_category_master_pk', $categoryId);
    }

    /**
     * Scope to filter by priority.
     */
    public function scopeByPriority($query, $priorityId)
    {
        return $query->where('issue_priority_master_pk', $priorityId);
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute()
    {
        return match($this->issue_status) {
            self::STATUS_REPORTED => 'Reported',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_PENDING => 'Pending',
            self::STATUS_REOPENED => 'Reopened',
            default => 'Unknown',
        };
    }

    /**
     * Get behalf label.
     */
    public function getBehalfLabelAttribute()
    {
        return $this->behalf == self::BEHALF_CENTCOM ? 'Centcom' : 'MySelf';
    }

    /**
     * Check if issue was reported on behalf (Centcom).
     */
    public function isReportedOnBehalf()
    {
        return $this->created_by != $this->issue_logger || $this->behalf == self::BEHALF_CENTCOM;
    }

    /**
     * Get full image path.
     */
    public function getFullImagePathAttribute()
    {
        if ($this->image_name) {
            return asset('storage/issue_images/' . $this->pk . '_' . $this->image_name);
        }
        return null;
    }

    /**
     * Get thumbnail image path.
     */
    public function getThumbnailImagePathAttribute()
    {
        if ($this->image_name) {
            return asset('storage/issue_images/tmb_' . $this->pk . '_' . $this->image_name);
        }
        return null;
    }
}
