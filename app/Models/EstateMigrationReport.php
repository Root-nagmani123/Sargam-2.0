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
    ];

    protected $casts = [
        'allotment_year' => 'integer',
    ];
}
