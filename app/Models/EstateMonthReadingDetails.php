<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstateMonthReadingDetails extends Model
{
    protected $table = 'estate_month_reading_details';

    protected $primaryKey = 'pk';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'estate_possession_details_pk',
        'from_date',
        'to_date',
        'last_month_elec_red',
        'curr_month_elec_red',
        'last_month_elec_red2',
        'curr_month_elec_red2',
        'bill_month',
        'bill_year',
        'house_no',
        'meter_one',
        'meter_two',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
    ];

}
