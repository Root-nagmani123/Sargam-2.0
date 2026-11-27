<?php

namespace Modules\GuestTaClaim\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\GuestTaClaim\Enums\Permission;

class UpdatePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [Rule::in(Permission::values())],
        ];
    }
}
