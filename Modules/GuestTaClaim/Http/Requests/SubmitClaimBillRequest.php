<?php

namespace Modules\GuestTaClaim\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitClaimBillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bill_pdf' => [
                'required',
                'file',
                'mimes:pdf',
                'max:'.config('guest-ta-claim.attachments.max_kb'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'bill_pdf.required' => 'Upload the signed bill PDF before sending the claim directly to Accounts.',
            'bill_pdf.mimes' => 'The signed bill must be a PDF file.',
        ];
    }
}
