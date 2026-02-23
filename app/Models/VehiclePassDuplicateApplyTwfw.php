<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Case 7 - Vehicle Pass Duplicate: Both 2W and 4W.
 * Table: vehicle_pass_duplicate_apply_TWFW (exact SQL structure)
 * Primary key: vehicle_tw_pk (e.g. DUP001)
 * vehicle_primary_pk = original pass (TW00066, FW00058)
 * card_reason = damage, lost, stolen, --- etc
 */
class VehiclePassDuplicateApplyTwfw extends Model
{
    protected $table = 'vehicle_pass_duplicate_apply_TWFW';

    protected $primaryKey = 'vehicle_tw_pk';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    const STATUS_PENDING = 1;

    const STATUS_APPROVED = 2;

    const STATUS_REJECTED = 3;

    const VEHICLE_TYPE_2W = 2;

    const VEHICLE_TYPE_4W = 1;

    protected $fillable = [
        'vehicle_tw_pk',
        'employee_id_card',
        'vehicle_type',
        'vehicle_no',
        'vehicle_req_id',
        'doc_upload',
        'vehicle_card_reapply',
        'veh_card_valid_from',
        'vech_card_valid_to',
        'vech_card_status',
        'app_remarks',
        'created_date',
        'veh_card_forward',
        'veh_card_genrated_date',
        'veh_card_forward_status',
        'veh_created_by',
        'emp_master_pk',
        'fir_doc',
        'paid_recepit',
        'card_reason',
        'vehicle_primary_pk',
    ];

    protected $casts = [
        'veh_card_valid_from' => 'date',
        'vech_card_valid_to' => 'date',
        'created_date' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(EmployeeMaster::class, 'emp_master_pk', 'pk');
    }

    public function vehicleType()
    {
        return $this->belongsTo(SecVehicleType::class, 'vehicle_type', 'pk');
    }

    public function createdBy()
    {
        return $this->belongsTo(EmployeeMaster::class, 'veh_created_by', 'pk');
    }

    public function approvals()
    {
        return $this->hasMany(VehiclePassDuplicateApplyApprovalTwfw::class, 'vehicle_TW_pk', 'vehicle_tw_pk');
    }

    public static function reasonOptions(): array
    {
        return [
            'Lost/Stolen' => 'Lost/Stolen',
            'Damaged' => 'Damaged',
            'Expired' => 'Expired (Extension)',
            'Other' => 'Other',
        ];
    }

    /** Map form reason to DB card_reason (lowercase). */
    public static function mapReasonToCardReason(string $reason): string
    {
        return match (strtolower($reason)) {
            'lost' => 'lost',
            'damaged' => 'damage',
            'stolen' => 'stolen',
            'expired' => 'expired',
            default => '---',
        };
    }

    /** Map DB card_reason back to form select value. */
    public static function cardReasonToFormValue(?string $cardReason): string
    {
        return match (strtolower($cardReason ?? '')) {
            'damage' => 'Damaged',
            'lost' => 'Lost',
            'stolen' => 'Stolen',
            'expired' => 'Expired',
            default => 'Other',
        };
    }

    public function getStatusTextAttribute()
    {
        return match ((int) $this->vech_card_status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            default => 'Unknown',
        };
    }

    /** For view compatibility and routes - use encrypted vehicle_tw_pk. */
    public function getEncryptedIdAttribute()
    {
        return encrypt($this->vehicle_tw_pk);
    }

    public function getVehicleNumberAttribute()
    {
        return $this->vehicle_no;
    }

    public function getIdCardNumberAttribute()
    {
        return $this->employee_id_card;
    }

    public function getStartDateAttribute()
    {
        return $this->veh_card_valid_from;
    }

    public function getEndDateAttribute()
    {
        return $this->vech_card_valid_to;
    }

    public function getStatusAttribute()
    {
        return $this->status_text;
    }

    public function getCreatedAtAttribute()
    {
        return $this->created_date;
    }

    /** Alias for vehicle_primary_pk (original pass no). */
    public function getVehiclePassNoAttribute()
    {
        return $this->vehicle_primary_pk;
    }

    /** Employee name from relation (table has no employee_name column). Fallback to employee_id_card if no match. */
    public function getEmployeeNameAttribute()
    {
        $e = $this->employee;
        if ($e) {
            $name = trim(($e->first_name ?? '') . ' ' . ($e->last_name ?? ''));
            return $name !== '' ? $name : ($e->emp_id ?? $this->employee_id_card);
        }
        return $this->employee_id_card ?: null;
    }

    /** Display label for card_reason. */
    public function getReasonForDuplicateDisplayAttribute()
    {
        return self::cardReasonToFormValue($this->card_reason);
    }

    /** Designation from employee relation. */
    public function getDesignationAttribute()
    {
        return $this->employee?->designation?->designation_name;
    }

    /** Department from employee relation. */
    public function getDepartmentAttribute()
    {
        return $this->employee?->department?->department_name;
    }

    /** Alias for view: reason_for_duplicate display. */
    public function getReasonForDuplicateAttribute()
    {
        return $this->reason_for_duplicate_display;
    }

    /** Vehicle category display (1=4W, 2=2W per sec_vehicle_type). */
    public function getVehicleCategoryDisplayAttribute()
    {
        return (int) $this->vehicle_type === self::VEHICLE_TYPE_4W ? 'Four Wheeler' : 'Two Wheeler';
    }
}
