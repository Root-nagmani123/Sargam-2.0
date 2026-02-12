<?php

namespace App\Support;

use App\Models\SecurityParmIdApply;
use App\Models\SecurityFamilyIdApply;
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
        $dto->created_at = $row->created_date;
        $dto->card_type = $row->card_type ?? '--';
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

        // Properties expected by show/edit views (security table has no equivalent – use defaults)
        $dto->employee_type = 'Permanent Employee';
        $dto->sub_type = null;
        $dto->father_name = null;
        $dto->academy_joining = null;
        $dto->section = null;
        $dto->approval_authority = null;
        $dto->vendor_organization_name = null;
        $dto->joining_letter = null;
        $dto->fir_receipt = null;
        $dto->payment_receipt = null;
        $dto->documents = null;
        $dto->updated_at = $row->created_date;

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
        $dto->card_type = null;
        $dto->section = null;
        $dto->designation = null;
        $dto->employee_name = $row->emp_id_apply;
        $dto->group_photo = null;
        $dto->created_at = $row->created_date;
        $dto->created_by = $row->created_by;
        return $dto;
    }
}
