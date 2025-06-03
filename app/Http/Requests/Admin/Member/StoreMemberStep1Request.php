<?php

namespace App\Http\Requests\Admin\Member;

use App\Models\CasteCategoryMaster;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\EmployeeMaster;
class StoreMemberStep1Request extends FormRequest
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
        // dd(array_keys(CasteCategoryMaster::GetSeatName()));
        return [
            'title' => 'required|string|max:20',
            'first_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'last_name' => 'required|string|max:100',
            'father_husband_name' => 'required|string|max:100',
            'marital_status' => [
                'required',
                Rule::in(array_keys(EmployeeMaster::maritalStatus))
            ],
            'gender' => [
                'required',
                Rule::in(array_keys(EmployeeMaster::gender))
            ],
            'caste_category' => [
                'required',
                Rule::in(array_keys(CasteCategoryMaster::GetSeatName()->toArray()))
            ],
            'height' => 'nullable|string|max:20',
            'date_of_birth' => 'required|date|before_or_equal:today',
        ];
    }

    public function messages()
    {
        return [
            'title.required' => 'The title field is required.',
            'first_name.required' => 'The first name field is required.',
            'last_name.required' => 'The last name field is required.',
            'father_husband_name.required' => 'Father/Husband name is required.',
            'marital_status.required' => 'Please select marital status.',
            'gender.required' => 'Please select gender.',
            'caste_category.required' => 'Please select caste category.',
            'date_of_birth.required' => 'Date of birth is required.',
            'date_of_birth.before_or_equal' => 'Date of birth cannot be in the future.',
        ];
    }
    
}
