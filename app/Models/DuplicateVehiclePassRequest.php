<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DuplicateVehiclePassRequest extends Model
{
    use SoftDeletes;

    protected $table = 'duplicate_vehicle_pass_requests';

    protected $fillable = [
        'vehicle_number',
        'vehicle_pass_no',
        'id_card_number',
        'emp_master_pk',
        'employee_name',
        'designation',
        'department',
        'vehicle_type',
        'start_date',
        'end_date',
        'reason_for_duplicate',
        'doc_upload',
        'status',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(EmployeeMaster::class, 'emp_master_pk', 'pk');
    }

    public function vehicleType()
    {
        return $this->belongsTo(SecVehicleType::class, 'vehicle_type', 'pk');
    }

    public function createdByUser()
    {
        return $this->belongsTo(EmployeeMaster::class, 'created_by', 'pk');
    }

    public static function statusOptions(): array
    {
        return ['Pending' => 'Pending', 'Approved' => 'Approved', 'Rejected' => 'Rejected', 'Issued' => 'Issued'];
    }

    public static function reasonOptions(): array
    {
        return [
            'Lost' => 'Lost',
            'Damaged' => 'Damaged',
            'Stolen' => 'Stolen',
            'Expired' => 'Expired (Extension)',
            'Other' => 'Other',
        ];
    }
}
