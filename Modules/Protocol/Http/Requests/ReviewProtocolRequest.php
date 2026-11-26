<?php

namespace Modules\Protocol\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Protocol\Services\ProtocolDirectory;

class ReviewProtocolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', 'in:approve,reject,recommend'],
            // Only someone on the review screen's approver list: they alone can act on a recommended request
            'recommended_to_id' => ['required_if:decision,recommend', 'nullable', 'integer', Rule::in(app(ProtocolDirectory::class)->approverLoginPks())],
            'remarks' => ['required_if:decision,reject', 'nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'recommended_to_id.required_if' => 'Please select who this request should be recommended to.',
            'recommended_to_id.in' => 'Please select a Protocol Approver from the list.',
            'remarks.required_if' => 'Remarks are required when rejecting a request.',
            'remarks.max' => 'Remarks cannot exceed 1000 characters.',
        ];
    }
}
