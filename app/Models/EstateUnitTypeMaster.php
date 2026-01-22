<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstateUnitTypeMaster extends Model
{
    use HasFactory;

    protected $table = 'estate_unit_type_master';
    protected $primaryKey = 'pk';

    protected $fillable = [
        'unit_type',
        'description',
        'created_by',
        'created_date',
        'modify_by',
        'modify_date',
    ];

    protected $casts = [
        'created_date' => 'datetime',
        'modify_date' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function units()
    {
        return $this->hasMany(EstateUnitMaster::class, 'estate_unit_type_master_pk', 'pk');
    }

    public function requests()
    {
        return $this->hasMany(EstateRequestDetails::class, 'estate_unit_type_master_pk', 'pk');
    }
}
