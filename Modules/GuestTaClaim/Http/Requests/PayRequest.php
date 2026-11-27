<?php

namespace Modules\GuestTaClaim\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'remark' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
