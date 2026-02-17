<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstateHomeRequestDetails extends Model
{
    protected $table = 'estate_home_request_details';

    protected $primaryKey = 'pk';

    public $incrementing = true;

    public $timestamps = false;

    protected $casts = [
        'req_date' => 'date',
        'doj_pay_scale' => 'date',
        'doj_academic' => 'date',
        'doj_service' => 'date',
    ];
}
