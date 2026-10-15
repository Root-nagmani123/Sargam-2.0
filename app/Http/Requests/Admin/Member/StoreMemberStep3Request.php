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
            // A pk that does not exist at all is rejected here. A pk that exists but was
            // deactivated while the form was open is NOT rejected — that failed the whole
            // save over one stale checkbox (PR #319 re-review F-045). It is still never
            // assigned: MemberController::withoutInactiveRoles() drops it and the response
            // says so, which keeps F-025's guarantee that only active roles are granted.
            'userrole.*' => ['integer', Rule::exists((new UserRoleMaster())->getTable(), 'pk')],
        ];
    }

    public function messages(): array
    {
        return [
            'userrole.required' => 'Please select a user role.',
            'userrole.*.integer' => 'Invalid role selected.',
            'userrole.*.exists' => 'Invalid role selected.',
        ];
    }
}
