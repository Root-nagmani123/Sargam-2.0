<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityEmployeeType extends Model
{
    protected $table = 'security_employee_type';
    protected $primaryKey = 'pk';
    public $timestamps = false;

    protected $fillable = [
        'employee_type_name',
        'active_inactive',
        'created_date',
        'modified_date',
    ];

    protected $casts = [
        'created_date' => 'datetime',
        'modified_date' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('active_inactive', 1);
    }
}
