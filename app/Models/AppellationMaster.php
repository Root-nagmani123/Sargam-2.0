<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppellationMaster extends Model
{
    protected $table = 'appellation_master';
    protected $primaryKey = 'pk';

    // Allow mass assignment
    protected $fillable = [
        'appettation_name',
        'active_inactive'
    ];

    // custom timestamp columns
    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'modified_date';

    protected $casts = [
        'active_inactive' => 'integer',
    ];
}