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
            'residencenumber'     => ['required', 'numeric', 'digits_between:6,15'],
            'miscellaneous'       => ['required', 'string', 'max:255'],

            // Validate uploaded image and documents
            'picture'             => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:500'], // max 500KB
            'additionaldocument'  => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:1024'], // max 1MB
        ];
    }

    public function messages(): array
    {
        return [
            'residencenumber.required' => 'Residence number is required.',
            'residencenumber.numeric'  => 'Residence number must be a valid number.',
            'residencenumber.digits_between' => 'Residence number must be between',
            'picture.image'        => 'The uploaded file must be an image.',
            'picture.mimes'        => 'Picture must be a file of type: jpg, jpeg, png.',
            'picture.max'          => 'Picture size must not exceed 2MB.',
            'additionaldocument.mimes' => 'Document must be of type: pdf, doc, docx, jpg, jpeg, or png.',
            'additionaldocument.max'   => 'Document size must not exceed 4MB.',
        ];
    }
}
