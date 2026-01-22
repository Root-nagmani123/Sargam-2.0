<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstateCampusMaster extends Model
{
    use HasFactory;

    protected $table = 'estate_campus_master';
    protected $primaryKey = 'pk';

    protected $fillable = [
        'campus_name',
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
    public function areas()
    {
        return $this->hasMany(EstateAreaMaster::class, 'estate_campus_master_pk', 'pk');
    }

    public function units()
    {
        return $this->hasMany(EstateUnitMaster::class, 'estate_campus_master_pk', 'pk');
    }

    public function wardens()
    {
        return $this->hasMany(EstateWardenMapping::class, 'estate_campus_master_pk', 'pk');
    }
}
