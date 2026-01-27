<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IssueLogHostelMap extends Model
{
    use HasFactory;

    protected $table = 'issue_log_hostel_map';
    protected $primaryKey = 'pk';
    public $timestamps = false;

    protected $fillable = [
        'issue_log_management_pk',
        'hostel_building_master_pk',
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
     * Get the hostel building.
     */
    public function hostelBuilding()
    {
        return $this->belongsTo(HostelBuildingMaster::class, 'hostel_building_master_pk', 'pk');
    }
}
