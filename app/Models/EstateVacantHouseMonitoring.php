<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstateVacantHouseMonitoring extends Model
{
    protected $table = 'estate_vacant_house_monitoring';

    protected $primaryKey = 'pk';

    public $timestamps = false;

    protected $fillable = [
        'estate_house_master_pk',
        'house_code',
        'house_name',
        'meter_number',
        'meter_number_two',
        'last_meter_reading_before_vacancy',
        'last_meter_reading_two_before_vacancy',
        'last_allottee_employee_name',
        'last_allottee_employee_master_pk',
        'last_allottee_other_req_pk',
        'estate_possession_details_pk',
        'estate_possession_other_pk',
        'possession_type',
        'vacancy_date',
        'is_active',
        'remarks',
        'created_date',
        'created_by',
        'modify_date',
        'modify_by',
    ];

    protected $casts = [
        'vacancy_date' => 'date',
        'is_active' => 'boolean',
    ];
}
