<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecVehicleType extends Model
{
    protected $table = 'sec_vehicle_type';
    protected $primaryKey = 'pk';
    public $timestamps = false;

    protected $fillable = [
        'vehicle_type',
        'description',
        'active_inactive',
        'created_date',
        'modified_date',
    ];

    protected $casts = [
        'created_date' => 'datetime',
        'modified_date' => 'datetime',
    ];

    public function passConfig()
    {
        return $this->hasOne(SecVehiclePassConfig::class, 'sec_vehicle_type_pk', 'pk');
    }

    public function vehicleApplications()
    {
        return $this->hasMany(VehiclePassTWApply::class, 'vehicle_type', 'pk');
    }

    public function scopeActive($query)
    {
        return $query->where('active_inactive', 1);
    }
}
