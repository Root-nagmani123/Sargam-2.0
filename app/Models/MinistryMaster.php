<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MinistryMaster extends Model
{
    protected $table = 'ministry_master';
    protected $primaryKey = 'pk';
    public $timestamps = true;

    protected $fillable = [
        'sector_master_pk',
        'ministry_name',
        'ministry_description',
        'status',
    ];

    protected $casts = [
        'status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship with SectorMaster
     */
    public function sector()
    {
        return $this->belongsTo(SectorMaster::class, 'sector_master_pk', 'pk');
    }

    /**
     * Scope: Get only active ministries
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1)->orderBy('ministry_name');
    }

    /**
     * Scope: Get ministries by sector
     */
    public function scopeBySector($query, $sectorPk)
    {
        return $query->where('sector_master_pk', $sectorPk)
            ->where('status', 1)
            ->orderBy('ministry_name');
    }
}
