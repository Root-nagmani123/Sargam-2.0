<?php

namespace Modules\Protocol\Http\Requests;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Modules\Protocol\Entities\ProtocolRequest;

class ManagerDecisionRequest extends FormRequest
{
    /**
     * Only the manager the request was recommended to may decide it, and only while it is still
     * recommended. Checked here so an unauthorised caller gets 403 before validation runs (PR #355 F-002).
     */
    public function authorize(): bool
    {
        $protocolRequest = $this->route('protocolRequest');

        return $protocolRequest instanceof ProtocolRequest
            && $protocolRequest->status === ProtocolRequest::STATUS_RECOMMENDED
            && $protocolRequest->recommended_to_id === Auth::id();
    }

    protected function failedAuthorization()
    {
        throw new AuthorizationException('This request is not awaiting your decision.');
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
