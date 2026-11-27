<?php

namespace Modules\GuestTaClaim\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchTimetableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route middleware checks claim.create
    }

    public function rules(): array
    {
        return [
            'course_id' => ['nullable', 'string', 'max:64'],
            'from' => ['nullable', 'required_with:course_id', 'date'],
            'to' => ['nullable', 'required_with:course_id', 'date', 'after_or_equal:from', 'before_or_equal:'.now()->addYear()->format('Y-m-d')],
        ];
    }

    public function messages(): array
    {
        return ['to.after_or_equal' => 'Choose a to date that’s on or after the from date.'];
    }
}
