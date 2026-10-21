<?php

namespace App\Http\Requests\Admin\Member;

use Illuminate\Foundation\Http\FormRequest;

class StoreMemberStep4Request extends FormRequest
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
            // Current Address
            'address'            => ['required', 'string', 'max:255'],
            'country'            => ['required', 'string'],
            'state'              => ['required', 'string'],
            'city'               => ['required', 'string'],
            'postal'             => ['required', 'string'],

            // Permanent Address
            'permanentaddress'   => ['required', 'string', 'max:255'],
            'permanentcountry'   => ['required', 'string'],
            'permanentstate'     => ['required', 'string'],
            'permanentcity'      => ['required', 'string'],
            'permanentpostal'    => ['required', 'string'],

            // Communication
            'personalemail'      => ['required', 'email'],
            'officialemail'      => ['required', 'email'],
            'mnumber'            => ['required', 'digits_between:10,15'],
            'emergencynumber'    => ['nullable', 'digits_between:10,15'],
            'landlinenumber'     => ['nullable', 'digits_between:6,15'],
        ];
    }

    public function messages(): array
    {
        return [
            // Current Address
            'address.required' => 'Current address is required.',
            'country.required' => 'Please select your country.',
            'state.required'   => 'Please select your state.',
            'city.required'    => 'City is required.',
            'postal.required'  => 'Postal code is required.',

            // Permanent Address
            'permanentaddress.required' => 'Permanent address is required.',
            'permanentcountry.required' => 'Please select your permanent country.',
            'permanentstate.required'   => 'Please select your permanent state.',
            'permanentcity.required'    => 'Permanent city is required.',
            'permanentpostal.required'  => 'Permanent postal code is required.',

            // Communication
            'personalemail.required'   => 'Personal email is required.',
            'personalemail.email'      => 'Enter a valid personal email.',
            'officialemail.required'   => 'Official email is required.',
            'officialemail.email'      => 'Enter a valid official email.',
            'mnumber.required'         => 'Mobile number is required.',
            'mnumber.digits_between'   => 'Mobile number must be between 10 to 15 digits.',
            'emergencynumber.digits_between' => 'Emergency contact must be between 10 to 15 digits.',
            'landlinenumber.digits_between'  => 'Landline number must be between 6 to 15 digits.',
        ];
    }
}
