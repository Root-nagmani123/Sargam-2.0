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
            'id' => 'required|string|max:50', //|unique:employees,employee_id
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
            
            'section.required' => 'Please select section',
            'section.exists' => 'Selected section is invalid',
        ];
    }
}
