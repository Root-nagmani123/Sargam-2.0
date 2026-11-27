<?php

namespace Modules\GuestTaClaim\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PrepareClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge([
            'course_id' => ['required', 'string', 'max:64'],
            'faculty_id' => ['required', 'string', 'max:64'],
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
            'sessions_confirmed' => ['required', 'accepted'],
        ], SessionRules::rules());
    }

    public function messages(): array
    {
        return [
            'sessions_confirmed.accepted' => 'Confirm the listed sessions before proceeding.',
        ];
    }
}
