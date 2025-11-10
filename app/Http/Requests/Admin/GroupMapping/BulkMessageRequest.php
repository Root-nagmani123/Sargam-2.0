<?php

namespace App\Http\Requests\Admin\GroupMapping;

use Illuminate\Foundation\Http\FormRequest;

class BulkMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'group_mapping_id' => ['required', 'string'],
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['required', 'string'],
            'channel' => ['required', 'in:sms,email'],
            'message' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'student_ids.required' => 'Please select at least one OT.',
            'student_ids.min' => 'Please select at least one OT.',
        ];
    }
}

