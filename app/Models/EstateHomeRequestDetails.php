<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstateHomeRequestDetails extends Model
{
    protected $table = 'estate_home_request_details';

    protected $primaryKey = 'pk';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'req_id',
        'req_date',
        'emp_name',
        'employee_id',
        'emp_designation',
        'pay_scale',
        'doj_pay_scale',
        'doj_academic',
        'doj_service',
        'eligibility_type_pk',
        'status',
        'remarks',
        'current_alot',
        'employee_pk',
        'app_status',
        'hac_status',
        'f_status',
        'change_status',
        'testing',
    ];

    protected $casts = [
        'req_date' => 'date',
        'doj_pay_scale' => 'date',
        'doj_academic' => 'date',
        'doj_service' => 'date',
    ];
}
