<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SectorMaster extends Model
{
    protected $table = 'sector_master';
    protected $primaryKey = 'pk';
    public $timestamps = true;

    protected $fillable = [
        'sector_name',
        'sector_description',
        'status',
    ];

    protected $casts = [
        'status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship with MinistryMaster
     */
    public function ministries()
    {
        return $this->hasMany(MinistryMaster::class, 'sector_master_pk', 'pk')
            ->where('status', 1)
            ->orderBy('ministry_name');
    }

    /**
     * Scope: Get only active sectors
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1)->orderBy('sector_name');
    }
}
