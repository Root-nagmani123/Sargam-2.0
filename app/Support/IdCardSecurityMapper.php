<?php

namespace App\Support;

use App\Models\SecurityParmIdApply;
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
     * Map SecurityParmIdApply to object compatible with employee_idcard views.
     */
    public static function toEmployeeRequestDto(SecurityParmIdApply $row): stdClass
    {
        $dto = new stdClass();
        $dto->id = $row->pk;
        $dto->pk = $row->pk;
        $dto->emp_id_apply = $row->emp_id_apply;
        $dto->name = $row->employee ? trim($row->employee->first_name . ' ' . ($row->employee->last_name ?? '')) : '--';
        $dto->designation = $row->employee && $row->employee->designation ? ($row->employee->designation->designation_name ?? '--') : '--';
        $dto->photo = $row->id_photo_path;
        $dto->joining_letter = $row->joining_letter_path ?? null;
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
        $dto->id_card_valid_upto = $row->card_valid_to ? $row->card_valid_to->format('d/m/Y') : null;
        $dto->id_card_valid_from = $row->card_valid_from ? $row->card_valid_from->format('d/m/Y') : null;
        $dto->id_card_number = $row->id_card_no;
        $dto->date_of_birth = $row->employee_dob;
        $dto->mobile_number = $row->mobile_no;
        $dto->telephone_number = $row->telephone_no;
        $dto->blood_group = $row->blood_group;
        $dto->remarks = $row->remarks;
        $dto->created_by = $row->created_by;

        $dto->id_status = $row->id_status;
        $dto->status = $row->status_label;
        $approvals = $row->approvals->sortBy('pk');
        $a1 = $approvals->where('status', 1)->first();
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
        $dto->approval_authority = null;
        $dto->vendor_organization_name = null;
        $dto->fir_receipt = null;
        $dto->payment_receipt = null;
        $dto->documents = null;
        $dto->updated_at = $row->created_date;

        return $dto;
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
        $dto->joining_letter = $row->joining_letter_path ?? null;
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
        $dto->id_card_valid_upto = isset($row->card_valid_to) ? \Carbon\Carbon::parse($row->card_valid_to)->format('d/m/Y') : null;
        $dto->id_card_valid_from = isset($row->card_valid_from) ? \Carbon\Carbon::parse($row->card_valid_from)->format('d/m/Y') : null;
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
        $dto->academy_joining = null;
        // section in table is bigint (department_master_pk). Resolve to department name for edit dropdown (value=name).
        $dto->section = null;
        if (!empty($row->section)) {
            $deptName = DB::table('department_master')->where('pk', $row->section)->value('department_name');
            $dto->section = $deptName ?? (string) $row->section;
        }
        // Contractual table: department_approval_emp_pk = Approval Authority (employee pk)
        $dto->approval_authority = isset($row->department_approval_emp_pk) ? (int) $row->department_approval_emp_pk : null;
        $dto->vendor_organization_name = $row->vender_name ?? null;
        $dto->fir_receipt = null;
        $dto->payment_receipt = null;
        $dto->documents = $row->doc_path ?? null;
        $dto->updated_at = $dto->created_at;
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
        $dto->card_type = null;
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
        return $dto;
    }
}
