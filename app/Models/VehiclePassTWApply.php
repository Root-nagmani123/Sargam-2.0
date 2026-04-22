<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VehiclePassTWApply extends Model
{
    /** Cache for name resolved by employee_id_card (emp_id / id_card_no) to avoid N+1 in lists. */
    protected static array $nameByEmployeeIdCardCache = [];
    protected $table = 'vehicle_pass_tw_apply';
    protected $primaryKey = 'vehicle_tw_pk';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    /** vehicle_pass_tw_apply.applicant_type: government_vehicle = 1, employee = 2, others = 3 */
    public const APPLICANT_TYPE_GOVERNMENT_VEHICLE = 1;

    public const APPLICANT_TYPE_EMPLOYEE = 2;

    public const APPLICANT_TYPE_OTHERS = 3;

    /**
     * Fillable aligned with SQL: vehicle_pass_tw_apply (pk, vehicle_tw_pk, employee_id_card, vehicle_type, vehicle_no, ...).
     */
    protected $fillable = [
        'vehicle_tw_pk',
        'employee_id_card',
        'emp_master_pk',
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
        'gov_veh',
        'applicant_type',
        'applicant_name',
        'designation',
        'department',
    ];

    protected $casts = [
        'veh_card_valid_from' => 'date',
        'vech_card_valid_to' => 'date',
        'created_date' => 'datetime',
        'veh_card_genrated_date' => 'datetime',
    ];

    /**
     * Map create/edit form value to DB flag.
     */
    public static function applicantTypeFormToInt(string $form): int
    {
        return match ($form) {
            'government_vehicle' => self::APPLICANT_TYPE_GOVERNMENT_VEHICLE,
            'employee' => self::APPLICANT_TYPE_EMPLOYEE,
            'others' => self::APPLICANT_TYPE_OTHERS,
            default => self::APPLICANT_TYPE_OTHERS,
        };
    }

    /**
     * Map DB flag (or legacy string) to form radio value for Blade/validation.
     */
    public static function applicantTypeToFormValue($stored): ?string
    {
        if ($stored === null || $stored === '') {
            return null;
        }
        if (is_string($stored)) {
            if (in_array($stored, ['employee', 'others', 'government_vehicle'], true)) {
                return $stored;
            }
            if (is_numeric($stored)) {
                $stored = (int) $stored;
            } else {
                return null;
            }
        }

        return match ((int) $stored) {
            self::APPLICANT_TYPE_GOVERNMENT_VEHICLE => 'government_vehicle',
            self::APPLICANT_TYPE_EMPLOYEE => 'employee',
            self::APPLICANT_TYPE_OTHERS => 'others',
            default => null,
        };
    }

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

    public function approval()
    {
        return $this->hasOne(VehiclePassTWApplyApproval::class, 'vehicle_TW_pk', 'vehicle_tw_pk');
    }

    public function approvals()
    {
        return $this->hasMany(VehiclePassTWApplyApproval::class, 'vehicle_TW_pk', 'vehicle_tw_pk');
    }

    public function getStatusTextAttribute()
    {
        return match($this->vech_card_status) {
            1 => 'Pending',
            2 => 'Approved',
            3 => 'Rejected',
            default => 'Unknown'
        };
    }

    public function getForwardStatusTextAttribute()
    {
        return match($this->veh_card_forward_status) {
            0 => 'Not Forwarded',
            1 => 'Forwarded',
            2 => 'Card Ready',
            default => 'Unknown'
        };
    }

    /**
     * Resolve employee name by matching employee_id_card to emp_id (employee_master) or id_card_no (security_parm_id_apply).
     * Result cached per request to avoid N+1 in lists.
     */
    public static function resolveNameByEmployeeIdCard(?string $employeeIdCard): ?string
    {
        if ($employeeIdCard === null || trim($employeeIdCard) === '') {
            return null;
        }
        $key = trim($employeeIdCard);
        if (isset(self::$nameByEmployeeIdCardCache[$key])) {
            return self::$nameByEmployeeIdCardCache[$key];
        }
        $name = null;
        if (Schema::hasColumn((new EmployeeMaster)->getTable(), 'emp_id')) {
            $emp = EmployeeMaster::query()
                ->where(function ($q) use ($key) {
                    $q->where('emp_id', $key)->orWhereRaw('TRIM(emp_id) = ?', [trim($key)]);
                })
                ->orderBy('pk')
                ->first(['pk', 'first_name', 'last_name']);
            if ($emp) {
                $name = trim(($emp->first_name ?? '') . ' ' . ($emp->last_name ?? ''));
            }
        }
        if (($name === null || $name === '') && ctype_digit($key)) {
            $emp = EmployeeMaster::query()
                ->where(function ($q) use ($key) {
                    $q->where('pk', $key);
                    if (Schema::hasColumn((new EmployeeMaster)->getTable(), 'pk_old')) {
                        $q->orWhere('pk_old', $key);
                    }
                })
                ->first(['pk', 'first_name', 'last_name']);
            if ($emp) {
                $name = trim(($emp->first_name ?? '') . ' ' . ($emp->last_name ?? ''));
            }
        }
        if (($name === null || $name === '') && Schema::hasTable('security_parm_id_apply')) {
            $row = DB::table('security_parm_id_apply')
                ->where(function ($q) use ($key) {
                    $q->where('id_card_no', $key)->orWhereRaw('TRIM(id_card_no) = ?', [trim($key)]);
                })
                ->value('employee_master_pk');
            if ($row) {
                $emp = EmployeeMaster::find($row, ['pk', 'first_name', 'last_name']);
                if ($emp) {
                    $name = trim(($emp->first_name ?? '') . ' ' . ($emp->last_name ?? ''));
                }
            }
        }
        if (($name === null || $name === '') && Schema::hasTable('security_con_oth_id_apply')) {
            $row = DB::table('security_con_oth_id_apply')
                ->where(function ($q) use ($key) {
                    $q->where('id_card_no', $key)->orWhereRaw('TRIM(COALESCE(id_card_no, "")) = ?', [trim($key)]);
                })
                ->value('created_by');
            if ($row) {
                $emp = EmployeeMaster::find($row, ['pk', 'first_name', 'last_name']);
                if ($emp) {
                    $name = trim(($emp->first_name ?? '') . ' ' . ($emp->last_name ?? ''));
                }
            }
        }
        self::$nameByEmployeeIdCardCache[$key] = ($name !== null && $name !== '') ? $name : null;
        return self::$nameByEmployeeIdCardCache[$key];
    }

    /**
     * Display name: from employee relation, applicant_name, or name resolved by employee_id_card (emp_id / id_card_no), else card/--.
     */
    public function getDisplayNameAttribute()
    {
        if ($this->employee) {
            $name = trim($this->employee->first_name . ' ' . ($this->employee->last_name ?? ''));
            if ($name !== '') {
                $id = $this->employee_id_card ?: ($this->employee->emp_id ?? '');
                return $id ? $name . ' (' . $id . ')' : $name;
            }
        }
        $applicantName = trim((string) ($this->applicant_name ?? ''));
        if ($applicantName !== '') {
            return $this->employee_id_card ? $applicantName . ' (' . $this->employee_id_card . ')' : $applicantName;
        }
        if ($this->employee_id_card) {
            $resolvedName = self::resolveNameByEmployeeIdCard($this->employee_id_card);
            if ($resolvedName !== null && $resolvedName !== '') {
                return $resolvedName . ' (' . $this->employee_id_card . ')';
            }
        }
        return $this->employee_id_card ?: '--';
    }
}
