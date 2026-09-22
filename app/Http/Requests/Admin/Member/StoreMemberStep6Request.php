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
            // PR #319 review F-009 asked for min:0 "and a sane maximum ... regardless" of
            // how the open precision question is settled. min:0 shipped in round 2; the
            // ceiling did not. 1,00,00,000 is far above any monthly basic pay here and far
            // below the signed-INT range the column currently allows, so a mistyped extra
            // digit is rejected at the form instead of being stored. The integer-vs-
            // decimal(15,2) half of F-009 is a domain decision and is NOT made here.
            // numeric, not integer: basic pay may carry paise, and the column is now
            // decimal(15,2) to match every other money column on payroll_salary_master
            // (PR #319 review, F-009). min:0 rejects a negative; the ceiling is far above
            // any monthly basic pay here, so a mistyped extra digit is caught at the form.
            'basicpay'         => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'bankname'         => ['nullable', 'string', 'max:100'],
            'accountno'        => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'gradepay.exists'         => 'The selected grade pay is invalid.',
            'employeecategory.exists' => 'The selected employee category is invalid.',
            'basicpay.numeric'        => 'Basic pay must be a valid amount.',
            'basicpay.min'            => 'Basic pay cannot be negative.',
            'basicpay.max'            => 'Basic pay must not exceed 1,00,00,000.',
            'bankname.max'            => 'Bank name must not exceed 100 characters.',
            'accountno.max'           => 'Account number must not exceed 50 characters.',
        ];
    }
}
