<?php

namespace Modules\GuestTaClaim\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $returning = $this->routeIs('guest-ta-claim.claims.return');

        return [
            'remark' => [$returning ? 'required' : 'nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return ['remark.required' => 'Add a remark so TA & Training knows what to fix.'];
    }
}
