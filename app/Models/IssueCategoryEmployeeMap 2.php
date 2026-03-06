<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IssueCategoryEmployeeMap extends Model
{
    use HasFactory;

    protected $table = 'issue_category_employee_map';
    protected $primaryKey = 'pk';
    public $timestamps = false;

    protected $fillable = [
        'employee_master_pk',
        'issue_category_master_pk',
        'created_date',
        'created_by',
        'days_notify',
        'priority',
    ];

    protected $casts = [
        'created_date' => 'datetime',
        'days_notify' => 'integer',
        'priority' => 'integer',
    ];

    /**
     * Get the category.
     */
    public function category()
    {
        return $this->belongsTo(IssueCategoryMaster::class, 'issue_category_master_pk', 'pk');
    }

    /**
     * Get the employee (if employee_master table exists).
     */
    public function employee()
    {
        return $this->belongsTo(EmployeeMaster::class, 'employee_master_pk', 'pk');
    }
}
