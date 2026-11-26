<?php

namespace Modules\Protocol\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Protocol\Entities\ProtocolRequest;

class StoreProtocolTicketFilesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $protocolRequest = $this->route('protocolRequest');

        return $protocolRequest instanceof ProtocolRequest
            && $protocolRequest->request_type === 'ticket'
            && $protocolRequest->status === ProtocolRequest::STATUS_APPROVED;
    }

    public function rules(): array
    {
        return [
            'ticket_files' => ['required', 'array', 'min:1', 'max:20'],
            'ticket_files.*' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,gif,webp', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'ticket_files.required' => 'Select at least one ticket file to upload.',
            'ticket_files.min' => 'Select at least one ticket file to upload.',
            'ticket_files.max' => 'You can upload up to 20 ticket files at a time.',
            'ticket_files.*.mimes' => 'Ticket files must be PDF, JPG, PNG, GIF, or WEBP images.',
            'ticket_files.*.max' => 'Each ticket file must be 10 MB or smaller.',
        ];
    }
}
