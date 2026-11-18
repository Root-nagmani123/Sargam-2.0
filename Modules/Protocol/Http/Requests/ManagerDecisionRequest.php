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
            // A rejection must say why; an approval may go without a note
            'remarks' => ['required_if:decision,reject', 'nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'remarks.required_if' => 'Remarks are required when rejecting a request.',
            'remarks.max' => 'Remarks cannot exceed 1000 characters.',
        ];
    }
}
