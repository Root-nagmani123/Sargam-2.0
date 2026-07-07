<?php

namespace App\Models\FC;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FcStaffActivityAccess extends Model
{
    protected $table = 'fc_staff_activity_access';

    protected $fillable = ['user_name', 'department_id'];

    public function department(): BelongsTo
    {
        return $this->belongsTo(FcActivityDepartment::class, 'department_id');
    }
}
