<?php

namespace App\Http\Requests\Admin\Member;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMemberStep2Request extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $empID = request()->emp_id ?? '';
        
        return [
            'type' => 'required|exists:employee_type_master,pk',
            // PR #319 review (F-001): this was free-form string|max:50, and its only consumer
            // is MemberDataTable::rawColumns()'s employee_id column. That column is now escaped
            // with e() too, but a format rule closes the same gap at the source rather than
            // relying solely on output escaping. Checked against live employee_master.emp_id
            // data before choosing this shape: 225 of the existing rows contain letters,
            // spaces or trailing whitespace (e.g. "a k singh", "COP00003   "), so an allowlist
            // narrow enough to look like a real employee-code format would break editing any
            // of them. This instead denies only the characters that matter for HTML injection
            // (<, >, ", ', &) — zero false positives against current data.
            'id' => ['required', 'string', 'max:50', 'regex:/^[^<>"\'&]+$/'], //|unique:employees,employee_id
            'group' => 'required', // |exists:employee_groups,id
            'designation' => 'required', // |exists:designations,id
            'userid'     => [
                'required',
                'string',
                'max:50',
                Rule::unique('user_credentials', 'user_name')->ignore($empID, 'user_id'),
            ],
            'section' => 'required|exists:department_master,pk',
        ];
    }

    public function messages()
    {
        return [
            'type.required' => 'Please select employee type',
            'type.exists' => 'Selected employee type is invalid',
            
            'id.required' => 'Employee ID is required',
            'id.max' => 'Employee ID must not exceed 50 characters',
            'id.regex' => 'Employee ID must not contain <, >, ", \', or & characters',
            'id.unique' => 'This employee ID already exists',
            
            'group.required' => 'Please select employee group',
            'group.exists' => 'Selected employee group is invalid',
            
            'designation.required' => 'Please select designation',
            'designation.exists' => 'Selected designation is invalid',
            
            'userid.required' => 'User ID is required',
            'userid.max' => 'User ID must not exceed 50 characters',
            'userid.unique' => 'This user ID already exists',
            
            'section.required' => 'Please select department',
            'section.exists' => 'Selected department is invalid',
        ];
    }
}
