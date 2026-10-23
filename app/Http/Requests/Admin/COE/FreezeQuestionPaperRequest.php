<?php

namespace App\Http\Requests\Admin\COE;

use App\Services\COE\FreezeOtpService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Freeze (faculty): the OTP sent to their mobile. Whether it is right, and
 * which paper they may freeze, is checked by the service.
 */
class FreezeQuestionPaperRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['otp' => ['required', 'string', 'digits:'.FreezeOtpService::DIGITS]];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'otp.required' => 'Enter the OTP sent to your phone.',
            'otp.digits' => 'Enter the '.FreezeOtpService::DIGITS.'-digit OTP sent to your phone.',
        ];
    }
}
