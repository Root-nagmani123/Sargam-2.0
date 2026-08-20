<?php

namespace App\Http\Requests\Admin\Member;

use Illuminate\Foundation\Http\FormRequest;

class StoreMemberStep6Request extends FormRequest
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
        return [
            'gradepay'         => ['nullable', 'exists:salary_grade_master,pk'],
            'employeecategory' => ['nullable', 'exists:employee_category_master,pk'],
            'basicpay'         => ['nullable', 'integer'],
            'bankname'         => ['nullable', 'string', 'max:100'],
            'accountno'        => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'gradepay.exists'         => 'The selected grade pay is invalid.',
            'employeecategory.exists' => 'The selected employee category is invalid.',
            'basicpay.integer'        => 'Basic pay must be an integer value.',
            'bankname.max'            => 'Bank name must not exceed 100 characters.',
            'accountno.max'           => 'Account number must not exceed 50 characters.',
        ];
    }
}
