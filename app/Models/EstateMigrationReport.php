<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * estate_migration_report_1998_2026 – historical estate allotment migration data (1998–2026).
 */
class EstateMigrationReport extends Model
{
    protected $table = 'estate_migration_report_1998_2026';

    protected $primaryKey = 'id';

    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'allotment_year',
        'campus_name',
        'building_name',
        'type_of_building',
        'house_no',
        'employee_name',
        'department_name',
        'employee_type',
        'date_of_allotment',
        'date_of_exit',
        'occupancy_status',
        'total_stay_years',
        'total_stay_months',
        'total_stay_days',
        'stay_period_text',
    ];

    protected $casts = [
        'allotment_year'    => 'integer',
        'date_of_allotment' => 'date',
        'date_of_exit'      => 'date',
        'total_stay_years'  => 'integer',
        'total_stay_months' => 'integer',
        'total_stay_days'   => 'integer',
    ];
}   