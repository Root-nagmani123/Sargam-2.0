<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstateChangeHomeReqDetails extends Model
{
    protected $table = 'estate_change_home_req_details';

    protected $primaryKey = 'pk';

    public $incrementing = true;

    public $timestamps = false;

    public function estateHomeRequestDetails()
    {
        return $this->belongsTo(EstateHomeRequestDetails::class, 'estate_home_req_details_pk', 'pk');
    }
}
