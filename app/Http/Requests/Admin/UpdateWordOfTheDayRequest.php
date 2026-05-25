<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateWordOfTheDayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('word'));
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('scheduled_date') && $this->input('scheduled_date') === '') {
            $this->merge(['scheduled_date' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'hindi_text' => ['required', 'string', 'max:255'],
            'english_text' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:99999'],
            'scheduled_date' => ['nullable', 'date'],
        ];
    }
}
