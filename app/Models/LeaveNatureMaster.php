<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveNatureMaster extends Model
{
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
}
