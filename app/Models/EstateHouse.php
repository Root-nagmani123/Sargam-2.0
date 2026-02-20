<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * estate_house_master (from estate_module_tables SQL).
 * Columns: pk, estate_campus_master_pk, estate_unit_master_pk, estate_block_master_pk,
 * estate_unit_sub_type_master_pk, licence_fee, remarks, house_no, vacant_renovation_status,
 * water_charge, electric_charge, meter_one, meter_two, used_home_status,
 * created_date, created_by, modify_date, modify_by
 */
class EstateHouse extends Model
{
    protected $table = 'estate_house_master';

    protected $primaryKey = 'pk';

    public $timestamps = false;

    protected $fillable = [
        'estate_campus_master_pk',
        'estate_unit_master_pk',
        'estate_block_master_pk',
        'estate_unit_sub_type_master_pk',
        'licence_fee',
        'remarks',
        'house_no',
        'vacant_renovation_status',
        'water_charge',
        'electric_charge',
        'meter_one',
        'meter_two',
        'used_home_status',
        'created_date',
        'created_by',
        'modify_date',
        'modify_by',
    ];

    protected $casts = [
        'licence_fee' => 'decimal:2',
        'water_charge' => 'decimal:2',
        'electric_charge' => 'decimal:2',
        'meter_one' => 'integer',
        'meter_two' => 'integer',
        'vacant_renovation_status' => 'integer',
        'used_home_status' => 'integer',
    ];

    public function campus()
    {
        return $this->belongsTo(EstateCampus::class, 'estate_campus_master_pk', 'pk');
    }

    public function block()
    {
        return $this->belongsTo(EstateBlock::class, 'estate_block_master_pk', 'pk');
    }
}
