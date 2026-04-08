<?php

namespace App\Support;

use App\Models\SecurityParmIdApply;
use App\Models\SecurityParmIdApplyApproval;
use App\Models\SecurityFamilyIdApply;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * Maps security tables (security_parm_id_apply, security_family_id_apply)
 * to view-compatible DTOs so UI remains unchanged.
 */
class IdCardSecurityMapper
{
    /**
     * Format a date value (Carbon, DateTime, string, or null) to d/m/Y for display.
     */
    public static function formatDateForDisplay($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            if ($value instanceof \DateTimeInterface) {
                return $value->format('d/m/Y');
            }
            return \Carbon\Carbon::parse($value)->format('d/m/Y');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Resolve display name for employee_master.pk or pk_old (e.g. created_by on ID card rows).
     */
    private static function resolveEmployeeNameFromEmployeePk($employeePk): ?string
    {
        if ($employeePk === null || $employeePk === '') {
            return null;
        }
        $emp = DB::table('employee_master')
            ->where(function ($q) use ($employeePk) {
                $q->where('pk', $employeePk)->orWhere('pk_old', $employeePk);
            })
            ->select(['first_name', 'last_name'])
            ->first();
        if (! $emp) {
            return null;
        }
        $name = trim(($emp->first_name ?? '') . ' ' . ($emp->last_name ?? ''));

        return $name !== '' ? $name : null;
    }

    /**
     * Normalize a person's full name for comparison (uppercase, collapsed spaces).
     */
    private static function normalizeFullNameForMatch(?string $name): string
    {
        $s = trim((string) $name);
        if ($s === '') {
            return '';
        }

        return strtoupper(preg_replace('/\s+/u', ' ', $s) ?? $s);
    }

    /**
     * Resolve the employee_master row that the contractual ID card is for (beneficiary).
     * Uses created_by when it matches the name on the card; otherwise exact then fuzzy name match.
     */
    public static function resolveContractualBeneficiaryEmployee(object $row): ?object
    {
        $cardName = static::normalizeFullNameForMatch($row->employee_name ?? '');

        if (! empty($row->created_by)) {
            $byCreator = DB::table('employee_master')
                ->where(function ($q) use ($row) {
                    $q->where('pk', $row->created_by)->orWhere('pk_old', $row->created_by);
                })
                ->first(['pk', 'first_name', 'last_name', 'doj']);
            if ($byCreator) {
                $creatorName = static::normalizeFullNameForMatch(trim(($byCreator->first_name ?? '') . ' ' . ($byCreator->last_name ?? '')));
                if ($cardName === '' || $cardName === $creatorName) {
                    return $byCreator;
                }
            }
        }

        if ($cardName !== '') {
            $byName = DB::table('employee_master')
                ->whereRaw(
                    "UPPER(TRIM(CONCAT(COALESCE(TRIM(first_name),''),' ',COALESCE(TRIM(last_name),'')))) = ?",
                    [$cardName]
                )
                ->orderBy('pk')
                ->first(['pk', 'first_name', 'last_name', 'doj']);
            if ($byName) {
                return $byName;
            }
            $needle = trim((string) ($row->employee_name ?? ''));
            if ($needle !== '') {
                $fuzzy = DB::table('employee_master')
                    ->where(DB::raw("CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,''))"), 'like', '%' . $needle . '%')
                    ->orderBy('pk')
                    ->first(['pk', 'first_name', 'last_name', 'doj']);
                if ($fuzzy) {
                    return $fuzzy;
                }
            }
        }

        return null;
    }

    /**
     * Generate ID card number for government (Permanent) employees.
     * Format: DDMMYYYY(dob) + first 4 letters of name (uppercase).
     * Example: 20022026MAYA (20-Feb-2026, name "Maya").
     *
     * @param \DateTimeInterface|string|null $dob Date of birth
     * @param string $name Employee full name (first_name + last_name)
     * @return string|null Generated ID or null if dob/name insufficient
     */
    public static function generateGovernmentEmployeeIdCardNumber($dob, string $name): ?string
    {
        if (empty($dob)) {
            return null;
        }
        try {
            $date = $dob instanceof \DateTimeInterface
                ? $dob
                : \Carbon\Carbon::parse($dob);
            $ddmmyyyy = $date->format('dmY'); // DDMMYYYY
        } catch (\Exception $e) {
            return null;
        }
        $alphaOnly = preg_replace('/[^a-zA-Z]/', '', $name);
        $first4 = strtoupper(substr($alphaOnly, 0, 4));
        if (empty($first4)) {
            return null;
        }
        return $ddmmyyyy . $first4;
    }

    /**
     * Map SecurityParmIdApply to object compatible with employee_idcard views.
     */
    public static function toEmployeeRequestDto(SecurityParmIdApply $row): stdClass
    {
        $dto = new stdClass();
        $dto->id = $row->emp_id_apply;
        $dto->pk = $row->emp_id_apply;
        $dto->emp_id_apply = $row->emp_id_apply;
        $dto->name = $row->employee ? trim($row->employee->first_name . ' ' . ($row->employee->last_name ?? '')) : '--';
        $dto->designation = $row->employee && $row->employee->designation ? ($row->employee->designation->designation_name ?? '--') : '--';
        // Normalize photo path: if it doesn't contain a slash, treat as filename under idcard/photos
        $photoPath = $row->id_photo_path;
        if ($photoPath && strpos($photoPath, '/') === false) {
            $photoPath = 'idcard/photos/' . $photoPath;
        }
        $dto->photo = $photoPath;
        $joiningPath = $row->joining_letter_path ?? null;
        if ($joiningPath && strpos((string) $joiningPath, '/') === false) {
            $joiningPath = 'idcard/joining_letters/' . $joiningPath;
        }
        $dto->joining_letter = $joiningPath;
        $dto->created_at = $row->created_date;
        // Card type display name from sec_id_cardno_master (permanent_type = master pk)
        $cardTypeName = '--';
        if (!empty($row->permanent_type)) {
            $master = DB::table('sec_id_cardno_master')->where('pk', $row->permanent_type)->value('sec_card_name');
            if ($master) {
                $cardTypeName = $master;
            }
        }
        $dto->card_type = $cardTypeName;
        $dto->request_for = 'Own ID Card';
        $dto->duplication_reason = null;
        $dto->id_card_valid_upto = static::formatDateForDisplay($row->card_valid_to ?? null);
        $dto->id_card_valid_from = static::formatDateForDisplay($row->card_valid_from ?? null);
        $dto->id_card_number = $row->id_card_no;
        $dto->date_of_birth = $row->employee_dob;
        $dto->mobile_number = $row->mobile_no;
        $dto->telephone_number = $row->telephone_no;
        $dto->blood_group = $row->blood_group;
        $dto->remarks = $row->remarks;
        $dto->created_by = $row->created_by;
        $dto->extension_reason = $row->extension_reason ?? null;
        $dto->extension_document_path = $row->extension_document_path ?? null;

        $dto->id_status = $row->id_status;
        $dto->status = $row->status_label;
        $approvals = $row->approvals->sortBy('pk');
        // In some flows, level-1 is stored via recommend_status=1 even when status is not 1.
        $a1 = $approvals->first(function ($a) {
            return (int) ($a->status ?? 0) === 1 || (int) ($a->recommend_status ?? 0) === 1;
        });
        $a2 = $approvals->where('status', 2)->first();
        $rej = $approvals->where('status', 3)->last();
        $dto->approved_by_a1 = $a1 ? $a1->approval_emp_pk : null;
        $dto->approved_by_a2 = $a2 ? $a2->approval_emp_pk : null;
        $dto->rejected_by = $rej ? $rej->approval_emp_pk : null;
        $dto->approved_by_a1_at = $a1 ? $a1->created_date : null;
        $dto->approved_by_a2_at = $a2 ? $a2->created_date : null;
        $dto->rejected_at = $rej ? $rej->created_date : null;
        $dto->rejection_reason = $rej ? $rej->approval_remarks : null;
        $dto->approver1 = $a1 && $a1->relationLoaded('approver') ? $a1->approver : null;
        $dto->approver2 = $a2 && $a2->relationLoaded('approver') ? $a2->approver : null;
        $dto->rejectedByUser = $rej && $rej->relationLoaded('approver') ? $rej->approver : null;
        $dto->employee_master_pk = $row->employee_master_pk;
        $dto->card_valid_from = $row->card_valid_from;
        $dto->card_valid_to = $row->card_valid_to;

        // Sub type display name from sec_id_cardno_config_map (perm_sub_type = config map pk)
        $subTypeName = null;
        if (!empty($row->perm_sub_type)) {
            $configRow = DB::table('sec_id_cardno_config_map')->where('pk', $row->perm_sub_type)->first();
            if ($configRow && !empty($configRow->config_name)) {
                $subTypeName = $configRow->config_name;
            }
        }
        $dto->sub_type = $subTypeName;

        // From employee when loaded
        $dto->employee_type = 'Permanent Employee';
        $dto->father_name = $row->employee ? ($row->employee->father_name ?? null) : null;
        $dto->academy_joining = $row->employee && $row->employee->doj
            ? (\Carbon\Carbon::parse($row->employee->doj)->format('Y-m-d'))
            : null;
        $dto->section = null;
        $dto->requested_by = null;
        $dto->requested_section = null;
        if ($row->relationLoaded('creator') && $row->creator) {
            $dto->requested_by = trim($row->creator->first_name . ' ' . ($row->creator->last_name ?? ''));
            if ($row->creator->relationLoaded('department') && $row->creator->department) {
                $dto->requested_section = $row->creator->department->department_name ?? null;
            }
        }
        if ($dto->requested_by === null && !empty($row->created_by)) {
            $creator = DB::table('employee_master')
                ->where(function ($q) use ($row) {
                    $q->where('pk', $row->created_by)->orWhere('pk_old', $row->created_by);
                })
                ->first();
            if ($creator) {
                $dto->requested_by = trim(($creator->first_name ?? '') . ' ' . ($creator->last_name ?? ''));
                if (!empty($creator->department_master_pk)) {
                    $dept = DB::table('department_master')->where('pk', $creator->department_master_pk)->value('department_name');
                    $dto->requested_section = $dept;
                }
            }
        }
        $dto->created_by_name = null;
        if ($row->relationLoaded('creator') && $row->creator) {
            $n = trim(($row->creator->first_name ?? '') . ' ' . ($row->creator->last_name ?? ''));
            $dto->created_by_name = $n !== '' ? $n : null;
        }
        if ($dto->created_by_name === null) {
            $dto->created_by_name = static::resolveEmployeeNameFromEmployeePk($row->created_by);
        }
        $dto->approval_authority = null;
        $dto->approval_authority_name = null;
        $dto->vendor_organization_name = null;
        $dto->fir_receipt = null;
        $dto->payment_receipt = null;
        $dto->documents = null;
        $dto->updated_at = $row->created_date;
        $dto->user_may_edit_request = static::permanentEmployeeIdCardApplicantMayEdit($row);

        return $dto;
    }

    /**
     * True when the applicant may still edit/delete: pending and no security approval row has progressed.
     */
    private static function permanentEmployeeIdCardApplicantMayEdit(SecurityParmIdApply $row): bool
    {
        if ((int) $row->id_status !== SecurityParmIdApply::ID_STATUS_PENDING) {
            return false;
        }
        $approvals = $row->relationLoaded('approvals') ? $row->approvals : $row->approvals()->get(['status', 'recommend_status']);
        foreach ($approvals as $a) {
            $st = (int) ($a->status ?? 0);
            if (in_array($st, [
                SecurityParmIdApplyApproval::STATUS_APPROVAL_1,
                SecurityParmIdApplyApproval::STATUS_APPROVAL_2,
                SecurityParmIdApplyApproval::STATUS_REJECTED,
            ], true)) {
                return false;
            }
            if ((int) ($a->recommend_status ?? 0) === 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * True when the applicant may still edit/delete: pending, section has not forwarded (depart_approval_status≠2),
     * and no security approval row has progressed.
     *
     * @param  \Illuminate\Support\Collection|\Traversable|array  $approvals
     */
    private static function contractualApplicantMayEditRequest(object $row, iterable $approvals, int $idStatus): bool
    {
        if ($idStatus !== SecurityParmIdApply::ID_STATUS_PENDING) {
            return false;
        }
        if ((int) ($row->depart_approval_status ?? 0) === 2) {
            return false;
        }
        foreach ($approvals as $a) {
            $st = (int) ($a->status ?? 0);
            if (in_array($st, [1, 2, 3], true)) {
                return false;
            }
            if ((int) ($a->recommend_status ?? 0) === 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * Map security_con_oth_id_apply row (Contractual) to object compatible with employee_idcard views.
     * Use id = 'c-' . pk so list/show/edit can resolve to contractual table.
     */
    public static function toContractualRequestDto(object $row): stdClass
    {
        $dto = new stdClass();
        $pk = (int) ($row->pk ?? 0);
        $dto->id = 'c-' . $pk;
        $dto->pk = $pk;
        $dto->emp_id_apply = $row->emp_id_apply ?? '';
        $dto->name = $row->employee_name ?? '--';
        $dto->designation = $row->designation_name ?? '--';
        // Photo: DB may store full path (idcard/photos/xyz.jpg) or just filename (xyz.jpg)
        $photoPath = $row->id_photo_path ?? null;
        if ($photoPath && strpos($photoPath, '/') === false) {
            $photoPath = 'idcard/photos/' . $photoPath;
        }
        $dto->photo = $photoPath;
        // Contractual: supporting document is doc_path only. Do not set joining_letter — same path caused duplicate rows in approval "Uploaded Documents".
        $docPathRaw = $row->doc_path ?? $row->joining_letter_path ?? null;
        $docPathNorm = $docPathRaw;
        if ($docPathNorm && strpos((string) $docPathNorm, '/') === false) {
            $docPathNorm = 'idcard/documents/' . $docPathNorm;
        }
        $dto->joining_letter = null;
        $dto->documents = $docPathNorm;
        $dto->created_at = isset($row->created_date) ? \Carbon\Carbon::parse($row->created_date) : null;
        $cardTypeName = '--';
        if (!empty($row->permanent_type)) {
            $master = DB::table('sec_id_cardno_master')->where('pk', $row->permanent_type)->value('sec_card_name');
            if ($master) {
                $cardTypeName = $master;
            }
        }
        $dto->card_type = $cardTypeName;
        $dto->request_for = 'Own ID Card';
        $dto->duplication_reason = null;
        $dto->id_card_valid_upto = static::formatDateForDisplay($row->card_valid_to ?? null);
        $dto->id_card_valid_from = static::formatDateForDisplay($row->card_valid_from ?? null);
        $dto->id_card_number = $row->id_card_no ?? null;
        $dto->date_of_birth = $row->employee_dob ?? null;
        $dto->mobile_number = $row->mobile_no ?? null;
        $dto->telephone_number = $row->telephone_no ?? null;
        $dto->blood_group = $row->blood_group ?? null;
        $dto->remarks = $row->remarks ?? null;
        $dto->created_by = $row->created_by ?? null;
        $idStatus = (int) ($row->id_status ?? 0);
        $dto->id_status = $idStatus;
        $dto->status = match ($idStatus) {
            1 => 'Pending',
            2 => 'Approved',
            3 => 'Rejected',
            default => 'Unknown',
        };
        // For contractual requests, resolve approval history from security_con_oth_id_apply_approval
        $applyId = $row->emp_id_apply ?? null;
        $dto->approved_by_a1 = null;
        $dto->approved_by_a2 = null;
        $dto->rejected_by = null;
        $dto->approved_by_a1_at = null;
        $dto->approved_by_a2_at = null;
        $dto->rejected_at = null;
        $dto->rejection_reason = null;
        $dto->approver1 = null;
        $dto->approver2 = null;
        $dto->rejectedByUser = null;

        $approvals = collect();
        if ($applyId) {
            $approvals = DB::table('security_con_oth_id_apply_approval')
                ->where('security_parm_id_apply_pk', $applyId)
                ->orderBy('pk')
                ->get();

            $a1 = $approvals->firstWhere('status', 1);
            $a2 = $approvals->firstWhere('status', 2);
            $rej = $approvals->where('status', 3)->last();

            $dto->approved_by_a1 = $a1 ? $a1->modified_by : null;
            $dto->approved_by_a2 = $a2 ? $a2->modified_by : null;
            $dto->rejected_by = $rej ? $rej->modified_by : null;
            $dto->approved_by_a1_at = $a1 ? $a1->created_date : null;
            $dto->approved_by_a2_at = $a2 ? $a2->created_date : null;
            $dto->rejected_at = $rej ? $rej->created_date : null;
            $dto->rejection_reason = $rej ? $rej->approval_remarks : null;

            // Resolve approver names from employee_master for display in "All Requests" table
            if ($dto->approved_by_a1) {
                $emp1 = DB::table('employee_master')->where('pk', $dto->approved_by_a1)->first();
                if ($emp1) {
                    $dto->approver1 = (object)[
                        'name' => trim(($emp1->first_name ?? '') . ' ' . ($emp1->last_name ?? '')),
                    ];
                }
            }
            if ($dto->approved_by_a2) {
                $emp2 = DB::table('employee_master')->where('pk', $dto->approved_by_a2)->first();
                if ($emp2) {
                    $dto->approver2 = (object)[
                        'name' => trim(($emp2->first_name ?? '') . ' ' . ($emp2->last_name ?? '')),
                    ];
                }
            }
            if ($dto->rejected_by) {
                $empR = DB::table('employee_master')->where('pk', $dto->rejected_by)->first();
                if ($empR) {
                    $dto->rejectedByUser = (object)[
                        'name' => trim(($empR->first_name ?? '') . ' ' . ($empR->last_name ?? '')),
                    ];
                }
            }
        }
        $dto->employee_master_pk = null;
        $dto->card_valid_from = isset($row->card_valid_from) ? \Carbon\Carbon::parse($row->card_valid_from) : null;
        $dto->card_valid_to = isset($row->card_valid_to) ? \Carbon\Carbon::parse($row->card_valid_to) : null;
        $subTypeName = null;
        if (!empty($row->perm_sub_type)) {
            $configRow = DB::table('sec_id_cardno_config_map')->where('pk', $row->perm_sub_type)->first();
            if ($configRow && !empty($configRow->config_name)) {
                $subTypeName = $configRow->config_name;
            }
        }
        $dto->sub_type = $subTypeName;
        $dto->employee_type = 'Contractual Employee';
        $dto->father_name = $row->father_name ?? null;
        $benef = static::resolveContractualBeneficiaryEmployee($row);
        $dto->academy_joining = ($benef && ! empty($benef->doj))
            ? \Carbon\Carbon::parse($benef->doj)->format('Y-m-d')
            : null;
        // section in table is bigint (department_master_pk). Resolve to department name for edit dropdown (value=name).
        $dto->section = null;
        $dto->requested_by = null;
        $dto->requested_section = null;
        if (!empty($row->section)) {
            $deptName = DB::table('department_master')->where('pk', $row->section)->value('department_name');
            $dto->section = $deptName ?? (string) $row->section;
            $dto->requested_section = $dto->section;
        }
        $dto->created_by_name = null;
        if (!empty($row->created_by)) {
            $creator = DB::table('employee_master')
                ->where(function ($q) use ($row) {
                    $q->where('pk', $row->created_by)->orWhere('pk_old', $row->created_by);
                })
                ->first();
            if ($creator) {
                $creatorName = trim(($creator->first_name ?? '') . ' ' . ($creator->last_name ?? ''));
                $dto->created_by_name = $creatorName !== '' ? $creatorName : null;
                $dto->requested_by = $creatorName !== '' ? $creatorName : null;
                if (!empty($creator->department_master_pk)) {
                    $dto->requested_section = DB::table('department_master')->where('pk', $creator->department_master_pk)->value('department_name');
                }
            }
        }
        // Contractual table: department_approval_emp_pk = Approval Authority (employee pk / pk_old)
        $dto->approval_authority = null;
        $dto->approval_authority_name = null;
        $deptApprPk = $row->department_approval_emp_pk ?? null;
        if ($deptApprPk !== null && $deptApprPk !== '') {
            $dto->approval_authority = (int) $deptApprPk;
            $authEmp = DB::table('employee_master as em')
                ->leftJoin('designation_master as dm', 'em.designation_master_pk', '=', 'dm.pk')
                ->where(function ($q) use ($deptApprPk) {
                    $q->where('em.pk', $deptApprPk)->orWhere('em.pk_old', $deptApprPk);
                })
                ->select(['em.first_name', 'em.last_name', 'dm.designation_name'])
                ->first();
            if ($authEmp) {
                $name = trim(($authEmp->first_name ?? '') . ' ' . ($authEmp->last_name ?? ''));
                $dto->approval_authority_name = $name !== ''
                    ? $name . (! empty($authEmp->designation_name) ? ' (' . $authEmp->designation_name . ')' : '')
                    : null;
            }
        }
        $dto->depart_approval_status = isset($row->depart_approval_status) ? (int) $row->depart_approval_status : null;
        $dto->vendor_organization_name = $row->vender_name ?? null;
        $dto->fir_receipt = null;
        $dto->payment_receipt = null;
        $dto->updated_at = $dto->created_at;
        $dto->user_may_edit_request = static::contractualApplicantMayEditRequest($row, $approvals, $idStatus);

        return $dto;
    }

    /**
     * Map SecurityFamilyIdApply to object compatible with family_idcard views.
     */
    public static function toFamilyRequestDto(SecurityFamilyIdApply $row): stdClass
    {
        $dto = new stdClass();
        $dto->id = $row->fml_id_apply;
        $dto->fml_id_apply = $row->fml_id_apply;
        $dto->name = $row->family_name;
        $dto->employee_id = $row->emp_id_apply;
        $dto->relation = $row->family_relation;
        $dto->family_photo = $row->family_photo ?? $row->id_photo_path;
        $dto->dob = $row->employee_dob;
        $dto->valid_from = $row->card_valid_from;
        $dto->valid_to = $row->card_valid_to;
        $dto->family_member_id = $row->id_card_no ?? null;
        // These are used directly in export views (PDF/Excel)
        $dto->card_type = $row->card_type ?? 'Family';
        $dto->section = null;
        $dto->designation = null;
        $dto->employee_name = $row->emp_id_apply;
        $dto->group_photo = null;
        $dto->created_at = $row->created_date;
        $dto->created_by = $row->created_by;
        $idStatus = (int) ($row->id_status ?? 1);
        $dto->id_status = $idStatus;
        $dto->status_label = match ($idStatus) {
            1 => 'Pending',
            2 => 'Approved',
            3 => 'Rejected',
            default => 'Unknown',
        };
        $dto->status = $dto->status_label;
        $dto->id_photo_path = $row->id_photo_path ?? null;
        
        // Fetch guardian (primary employee) details
        $guardianName = '--';
        $guardianDesignation = '--';
        $guardianSection = null;
        // Prefer created_by (employee pk) to resolve guardian details, fallback to emp_id_apply when needed.
        if (!empty($row->created_by)) {
            $employee = DB::table('employee_master')
                ->leftJoin('designation_master', 'designation_master.pk', '=', 'employee_master.designation_master_pk')
                ->leftJoin('department_master', 'department_master.pk', '=', 'employee_master.department_master_pk')
                ->where('employee_master.pk', $row->created_by)
                ->orWhere('employee_master.pk_old', $row->created_by)
                ->first([
                    'employee_master.first_name',
                    'employee_master.last_name',
                    'designation_master.designation_name',
                    'department_master.department_name',
                ]);

            if ($employee) {
                $guardianName = trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? ''));
                if ($guardianName === '' || $guardianName === null) {
                    $guardianName = $row->emp_id_apply ?: '--';
                }
                if (!empty($employee->designation_name)) {
                    $guardianDesignation = $employee->designation_name;
                }
                if (!empty($employee->department_name)) {
                    $guardianSection = $employee->department_name;
                }
            }
        } elseif (!empty($row->emp_id_apply)) {
            // Fallback: try to resolve via emp_id (card number or employee id)
            $employee = DB::table('employee_master')
                ->leftJoin('designation_master', 'designation_master.pk', '=', 'employee_master.designation_master_pk')
                ->leftJoin('department_master', 'department_master.pk', '=', 'employee_master.department_master_pk')
                ->where('employee_master.emp_id', $row->emp_id_apply)
                ->first([
                    'employee_master.first_name',
                    'employee_master.last_name',
                    'designation_master.designation_name',
                    'department_master.department_name',
                ]);

            if ($employee) {
                $guardianName = trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? '')) ?: $row->emp_id_apply;
                if (!empty($employee->designation_name)) {
                    $guardianDesignation = $employee->designation_name;
                }
                if (!empty($employee->department_name)) {
                    $guardianSection = $employee->department_name;
                }
            } else {
                $guardianName = $row->emp_id_apply;
            }
        }
        
        $dto->guardian_name = $guardianName;
        $dto->guardian_designation = $guardianDesignation;
        // For exports, surface guardian details as primary employee info
        $dto->employee_name = $guardianName;
        $dto->designation = $guardianDesignation !== '--' ? $guardianDesignation : null;
        if ($guardianSection !== null) {
            $dto->section = $guardianSection;
        }
        
        return $dto;
    }
}
