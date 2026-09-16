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

        // employee_master.emp_id is indexed but NOT unique, so the database
        // accepts a duplicate silently. The browser-side double-submit guard
        // cannot see a re-POST after a refresh, a second tab, a replayed
        // request, or a client where the JS never loaded, so the check has to
        // live here. Existing duplicates in the table are left alone - this
        // refuses NEW ones; the unique constraint is blocked on a data cleanup
        // and on confirming emp_id is the intended business key.
        $uniqueEmpId = Rule::unique('employee_master', 'emp_id');

        // On update the row being edited is itself a match and must not count.
        // On create there is no such row, and `ignore('')` would compare an
        // integer key against an empty string, so the clause is added only when
        // there is a key to ignore.
        if ($empID !== '') {
            $uniqueEmpId->ignore($empID, 'pk');
        }

        return [
            'type' => 'required|exists:employee_type_master,pk',
            'id' => ['required', 'string', 'max:50', $uniqueEmpId],
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
