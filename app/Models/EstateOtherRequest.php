<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstateOtherRequest extends Model
{
    use HasFactory;

    protected $table = 'estate_other_req';

    protected $primaryKey = 'pk';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        // employee_master.emp_id — Other/contract request ko us employee se jodta hai taaki
        // "My Estate Bill" (?scope=self) me use apna bill dikhe.
        'employee_master_emp_id',
        'emp_name',
        'f_name',
        'section',
        'doj_acad',
        'status',
        'request_no_oth',
        'mobile',
        'email',
        'office_extension_no',
        'residence_no',
        'designation',
    ];

    protected $casts = [
        'doj_acad' => 'date',
        'status' => 'integer',
    ];
}