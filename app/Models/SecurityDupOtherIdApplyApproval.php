<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityDupOtherIdApplyApproval extends Model
{
    protected $table = 'security_dup_other_id_apply_approval';

    protected $primaryKey = 'pk';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'created_date' => 'datetime',
        'modified_date' => 'datetime',
    ];
}

