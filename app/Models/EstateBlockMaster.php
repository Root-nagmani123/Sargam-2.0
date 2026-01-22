<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstateBlockMaster extends Model
{
    use HasFactory;

    protected $table = 'estate_block_master';
    protected $primaryKey = 'pk';

    protected $fillable = [
        'block_name',
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
        return $this->belongsToMany(
            EstateAreaMaster::class,
            'estate_area_block_mapping',
            'estate_block_master_pk',
            'estate_area_master_pk',
            'pk',
            'pk'
        );
    }

    public function units()
    {
        return $this->hasMany(EstateUnitMaster::class, 'estate_block_master_pk', 'pk');
    }
}
