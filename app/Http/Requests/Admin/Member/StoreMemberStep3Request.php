<?php

namespace App\Http\Requests\Admin\Member;

use Illuminate\Foundation\Http\FormRequest;

class StoreMemberStep3Request extends FormRequest
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
            'userrole' => ['required', 'array'], // you may replace with: exists:roles,id if it's from DB
            // 'styled_max_checkbox' => ['required', 'array', 'min:1', 'max:2'], // max 2 checkboxes allowed
        ];
    }

    public function messages(): array
    {
        return [
            'userrole.required' => 'Please select a user role.',
            // 'styled_max_checkbox.required' => 'Please select at least one role option.',
            // 'styled_max_checkbox.max' => 'You can select up to 2 role options only.',
        ];
    }
}
