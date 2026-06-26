<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StationedLeaveFacultyApprover extends Model
{
    protected $table = 'stationed_leave_faculty_approver';

    protected $primaryKey = 'pk';

    public $timestamps = false;

    protected $fillable = [
        'stationed_leave_master_pk',
        'faculty_master_pk',
        'is_approval_authority',
        'created_date',
        'modified_date',
    ];

    protected $casts = [
        'is_approval_authority' => 'boolean',
    ];

    public function stationedLeave()
    {
        return $this->belongsTo(StationedLeaveMaster::class, 'stationed_leave_master_pk', 'pk');
    }

    public function faculty()
    {
        return $this->belongsTo(FacultyMaster::class, 'faculty_master_pk', 'pk');
    }
}
