<?php

namespace App\Http\Requests\Admin\Member;

use Illuminate\Foundation\Http\FormRequest;

class StoreMemberStep5Request extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'homeaddress'         => ['nullable', 'string', 'max:255'],
            'residencenumber'     => ['nullable', 'numeric', 'digits_between:6,15'],
            // 'miscellaneous'       => ['required', 'string', 'max:255'],

            // Validate uploaded image and documents
            'picture'             => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:500'], // max 500KB
            'additionaldocument'  => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:1024'], // max 1MB
        ];
    }

    /**
     * PR #319 review round 8 (F-036): these messages used to announce 2MB / 4MB and a
     * doc+docx document type, while rules() enforces max:500 (500 KB), max:1024 (1 MB)
     * and mimes:pdf,jpg,jpeg,png. Laravel's max: rule on an uploaded file counts
     * kilobytes, so the stated ceilings were four times the enforced ones and doc/docx
     * were offered but rejected — a user given those messages has no way to reach a file
     * the form will accept. The messages are corrected to the rules rather than the other
     * way round: the rules are what executes, and the inline comments beside them record
     * the same 500 KB / 1 MB intent. Raising the limits instead is a product decision,
     * not one this fix makes silently.
     */
    public function messages(): array
    {
        return [
            'residencenumber.numeric'  => 'Residence number must be a valid number.',
            'residencenumber.digits_between' => 'Residence number must be between 6 and 15 digits.',
            'picture.image'        => 'The uploaded file must be an image.',
            'picture.mimes'        => 'Picture must be a file of type: jpg, jpeg, png.',
            'picture.max'          => 'Picture size must not exceed 500 KB.',
            'additionaldocument.mimes' => 'Document must be of type: pdf, jpg, jpeg, or png.',
            'additionaldocument.max'   => 'Document size must not exceed 1 MB.',
        ];
    }
}
