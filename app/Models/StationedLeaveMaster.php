<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StationedLeaveMaster extends Model
{
    protected $table = 'stationed_leave_master';

    protected $primaryKey = 'pk';

    public $timestamps = false;

    protected $fillable = [
        'course_master_pk',
        'effective_from',
        'apply_cutoff_time',
        'is_faculty_approval_required',
        'active_inactive',
        'created_by',
        'created_date',
        'modified_date',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'is_faculty_approval_required' => 'boolean',
    ];

    public function course()
    {
        return $this->belongsTo(CourseMaster::class, 'course_master_pk', 'pk');
    }

    public function approvers()
    {
        return $this->hasMany(StationedLeaveFacultyApprover::class, 'stationed_leave_master_pk', 'pk');
    }
}
