<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeCategoryMaster extends Model
{
    protected $table = 'employee_category_master';

    protected $primaryKey = 'pk';

    public $timestamps = false;

    protected $fillable = [
        'category',
    ];
}
