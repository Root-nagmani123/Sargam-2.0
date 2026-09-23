<?php

namespace Modules\Protocol\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewProtocolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', 'in:approve,recommend'],
            'recommended_to_id' => ['required_if:decision,recommend', 'nullable', 'integer', 'exists:users,id'],
            'assigned_guest_house' => ['nullable', 'string', 'max:150'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'recommended_to_id.required_if' => 'Please select who this request should be recommended to.',
        ];
    }
}
