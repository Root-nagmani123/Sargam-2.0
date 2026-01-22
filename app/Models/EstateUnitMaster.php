<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstateUnitMaster extends Model
{
    use HasFactory;

    protected $table = 'estate_unit_master';
    protected $primaryKey = 'pk';

    protected $fillable = [
        'estate_campus_master_pk',
        'estate_area_master_pk',
        'estate_block_master_pk',
        'estate_unit_type_master_pk',
        'estate_unit_sub_type_master_pk',
        'unit_name',
        'house_address',
        'description',
        'capacity',
        'estate_value',
        'rent',
        'is_rent_applicable',
        'quantity',
        'facility_master_pk',
        'is_active',
        'created_by',
        'created_date',
        'modify_by',
        'modify_date',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'estate_value' => 'decimal:2',
        'rent' => 'decimal:2',
        'is_rent_applicable' => 'boolean',
        'quantity' => 'integer',
        'is_active' => 'boolean',
        'created_date' => 'datetime',
        'modify_date' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function campus()
    {
        return $this->belongsTo(EstateCampusMaster::class, 'estate_campus_master_pk', 'pk');
    }

    public function area()
    {
        return $this->belongsTo(EstateAreaMaster::class, 'estate_area_master_pk', 'pk');
    }

    public function block()
    {
        return $this->belongsTo(EstateBlockMaster::class, 'estate_block_master_pk', 'pk');
    }

    public function unitType()
    {
        return $this->belongsTo(EstateUnitTypeMaster::class, 'estate_unit_type_master_pk', 'pk');
    }

    public function unitSubType()
    {
        return $this->belongsTo(EstateUnitSubTypeMaster::class, 'estate_unit_sub_type_master_pk', 'pk');
    }

    public function possessions()
    {
        return $this->hasMany(EstatePossession::class, 'estate_unit_master_pk', 'pk');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    public function scopeAvailable($query)
    {
        return $query->active()->whereDoesntHave('possessions', function ($q) {
            $q->where('status', 'active');
        });
    }
}
