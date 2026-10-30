<?php

namespace Modules\Protocol\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ManagerDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', 'in:approve,reject'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
