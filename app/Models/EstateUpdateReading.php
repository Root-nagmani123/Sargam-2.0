<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstateUpdateReading extends Model
{
    protected $table = 'estate_update_reading';

    protected $primaryKey = 'pk';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'estate_campus_master_pk',
        'estate_unit_master_pk',
        'estate_block_master_pk',
        'estate_unit_sub_type_master_pk',
        'house_no',
        'new_meter_no_one',
        'new_meter_no_two',
        'new_meter_reading_one',
        'new_meter_reading_two',
        'old_meter_no_one',
        'old_meter_no_two',
        'old_meter_reading_one',
        'old_meter_reading_two',
        'meter_change_month',
        'meter_update_date',
        'estate_possession_details_pk',
        'employee_master_pk',
    ];

    protected $casts = [
        'meter_update_date' => 'datetime',
    ];
}
