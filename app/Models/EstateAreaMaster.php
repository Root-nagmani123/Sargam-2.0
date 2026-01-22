<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstateAreaMaster extends Model
{
    use HasFactory;

    protected $table = 'estate_area_master';
    protected $primaryKey = 'pk';

    protected $fillable = [
        'estate_campus_master_pk',
        'area_name',
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
    public function campus()
    {
        return $this->belongsTo(EstateCampusMaster::class, 'estate_campus_master_pk', 'pk');
    }

    public function blocks()
    {
        return $this->belongsToMany(
            EstateBlockMaster::class,
            'estate_area_block_mapping',
            'estate_area_master_pk',
            'estate_block_master_pk',
            'pk',
            'pk'
        );
    }

    public function units()
    {
        return $this->hasMany(EstateUnitMaster::class, 'estate_area_master_pk', 'pk');
    }
}
