<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IssueLogBuildingMap extends Model
{
    use HasFactory;

    protected $table = 'issue_log_building_map';
    protected $primaryKey = 'pk';
    public $timestamps = false;

    protected $fillable = [
        'issue_log_management_pk',
        'building_master_pk',
        'floor_name',
        'room_name',
    ];

    /**
     * Get the issue log.
     */
    public function issueLog()
    {
        return $this->belongsTo(IssueLogManagement::class, 'issue_log_management_pk', 'pk');
    }

    /**
     * Get the building (if building_master table exists).
     */
    public function building()
    {
        return $this->belongsTo(BuildingMaster::class, 'building_master_pk', 'pk');
    }
}
