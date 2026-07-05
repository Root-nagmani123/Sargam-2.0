<?php

namespace App\Models\FC;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FcActivityDepartmentUser extends Model
{
    protected $table = 'fc_activity_department_user';

    protected $fillable = [
        'fc_activity_department_id',
        'user_credentials_pk',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(FcActivityDepartment::class, 'fc_activity_department_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_credentials_pk', 'pk');
    }
}
