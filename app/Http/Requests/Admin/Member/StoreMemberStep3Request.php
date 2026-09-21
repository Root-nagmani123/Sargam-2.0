<?php

namespace App\Http\Requests\Admin\Member;

use App\Models\UserRoleMaster;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'userrole' => ['required', 'array'],
            // Scoped to the same active-role set the checkboxes are rendered from
            // (UserRoleMaster::getUserRoleList()), not the whole table, so a
            // deactivated role's pk is rejected the same as one that never
            // existed (PR #319 review, F-025).
            'userrole.*' => ['integer', Rule::in(UserRoleMaster::getUserRoleList()->keys()->all())],
        ];
    }

    public function messages(): array
    {
        return [
            'userrole.required' => 'Please select a user role.',
            'userrole.*.integer' => 'Invalid role selected.',
            'userrole.*.in' => 'Invalid role selected.',
        ];
    }
}
