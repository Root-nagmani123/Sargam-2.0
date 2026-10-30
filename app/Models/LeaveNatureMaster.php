<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveNatureMaster extends Model
{
    /**
     * Which form a nature belongs to.
     *
     * TYPE_LEAVE is the bucket the Training Section's "Apply Leave on Behalf of
     * OT" page draws from. It is a nature bucket, not an application type — that
     * page stores its applications as STATIONED_LEAVE, so the bucket exists only
     * to keep its dropdown separate from the officer trainee's own two forms.
     */
    public const TYPE_PT_EXEMPTION = 'PT_EXEMPTION';

    public const TYPE_STATIONED_LEAVE = 'STATIONED_LEAVE';

    public const TYPE_LEAVE = 'LEAVE';

    public const TYPE_LABELS = [
        self::TYPE_LEAVE => 'Leave',
        self::TYPE_STATIONED_LEAVE => 'Stationed Leave',
        self::TYPE_PT_EXEMPTION => 'PT Exemption',
    ];

    protected $table = 'leave_nature_master';

    protected $primaryKey = 'pk';

    public $timestamps = false;

    protected $fillable = [
        'leave_type',
        'nature_name',
        'display_order',
        'active_inactive',
        'created_date',
        'modified_date',
    ];

    public function getLeaveTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->leave_type] ?? (string) $this->leave_type;
    }

    /** Active natures of one bucket, in display order — what a form's dropdown lists. */
    public function scopeOfType($query, string $leaveType)
    {
        return $query->where('leave_type', $leaveType)
            ->where('active_inactive', 1)
            ->orderBy('display_order')
            ->orderBy('nature_name');
    }
}
