<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecVehiclePassConfig extends Model
{
    protected $table = 'sec_vehcl_pass_config';
    protected $primaryKey = 'pk';
    public $timestamps = false;

    protected $fillable = [
        'sec_vehicle_type_pk',
        'charges',
        'start_counter',
        'active_inactive',
        'created_date',
        'modified_date',
    ];

    protected $casts = [
        'charges' => 'decimal:2',
        'created_date' => 'datetime',
        'modified_date' => 'datetime',
    ];

    public function vehicleType()
    {
        return $this->belongsTo(SecVehicleType::class, 'sec_vehicle_type_pk', 'pk');
    }

    public function scopeActive($query)
    {
        return $query->where('active_inactive', 1);
    }
}
