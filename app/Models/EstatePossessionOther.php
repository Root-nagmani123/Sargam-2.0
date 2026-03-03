<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstatePossessionOther extends Model
{
    protected $table = 'estate_possession_other';

    protected $primaryKey = 'pk';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'estate_other_req_pk',
        'estate_campus_master_pk',
        'estate_unit_type_master_pk',
        'estate_block_master_pk',
        'estate_unit_sub_type_master_pk',
        'estate_house_master_pk',
        'possession_date_oth',
        'meter_reading_oth',
        'meter_reading_oth1',
        'status',
        'create_date',
        'created_by',
        'allotment_date',
        'last_meter_reading_date',
        'current_meter_reading_date',
        'house_no',
        'return_home_status',
        'remarks',
        'upload_document',
        'noc_document',
    ];

    protected $casts = [
        'possession_date_oth' => 'datetime',
        'allotment_date' => 'datetime',
        'last_meter_reading_date' => 'date',
        'current_meter_reading_date' => 'date',
        'meter_dam_change_date' => 'datetime',
    ];

    public function estateOtherRequest()
    {
        return $this->belongsTo(EstateOtherRequest::class, 'estate_other_req_pk', 'pk');
    }
}
