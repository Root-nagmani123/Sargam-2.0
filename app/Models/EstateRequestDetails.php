<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstateRequestDetails extends Model
{
    use HasFactory;

    protected $table = 'estate_request_details';
    protected $primaryKey = 'pk';

    protected $fillable = [
        'employee_master_pk',
        'estate_unit_type_master_pk',
        'request_date',
        'request_type',
        'reason',
        'status',
        'approved_by',
        'approved_date',
        'approval_remarks',
        'created_by',
        'created_date',
        'modify_by',
        'modify_date',
    ];

    protected $casts = [
        'request_date' => 'date',
        'approved_date' => 'date',
        'created_date' => 'datetime',
        'modify_date' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function unitType()
    {
        return $this->belongsTo(EstateUnitTypeMaster::class, 'estate_unit_type_master_pk', 'pk');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
}
