<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstateMonthReadingDetailsOther extends Model
{
    protected $table = 'estate_month_reading_details_other';

    protected $primaryKey = 'pk';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'estate_possession_other_pk',
        'from_date',
        'to_date',
        'last_month_elec_red',
        'curr_month_elec_red',
        'bill_month',
        'bill_year',
        'bill_no',
        'notify_employee_status',
        'process_status',
        'electricty_charges',
        'water_charges',
        'licence_fees',
        'last_month_elec_red2',
        'curr_month_elec_red2',
        'per_unit',
        'created_date',
        'payroll_recovery_head_amount',
        'meter_one_elec_charge',
        'meter_two_elec_charge',
        'house_no',
        'meter_one',
        'meter_two',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
    ];

    public function estatePossessionOther()
    {
        return $this->belongsTo(EstatePossessionOther::class, 'estate_possession_other_pk', 'pk');
    }
}
