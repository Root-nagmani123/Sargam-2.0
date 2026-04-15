<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\{City, FacultyMaster};
use Illuminate\Validation\Rule;

class FacultyRequest extends FormRequest
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

        $existingFaculty = FacultyMaster::find($this->faculty_id);
        $otherCityPks = City::whereRaw('LOWER(city_name) = ?', ['other'])->pluck('pk')->toArray();
        return [
            // Basic Info
            'country' => 'required|exists:country_master,pk',
            'current_sector' => 'required|integer|in:1,2',
            'joiningdate' => 'required|date',
            'email' => 'nullable|email:rfc,dns',
            'alternativeEmail' => 'nullable|email:rfc,dns',
            'mobile' => ['nullable', 'digits:10'],
            // ...existing code...
        ];
    }

    public function messages()
    {
        return [
            // Basic Info

            // 'facultyType.required' => 'Faculty type is required',
            // 'firstName.required' => 'First name is required',
            // 'middlename.required' => 'Middle name is required',
            // 'lastname.required' => 'Last name is required',
            // 'fullname.required' => 'Full name is required',
            // 'firstName.regex' => 'First name must contain only letters and spaces',
            // 'middlename.regex' => 'Middle name must contain only letters and spaces',
            // 'lastname.regex' => 'Last name must contain only letters and spaces',
            // 'fullname.regex' => 'Full name must contain only letters and spaces',
            // 'gender.required' => 'Gender is required',
            // 'landline.required' => 'Landline number is required',
            // 'mobile.required' => 'Mobile number is required',
            // 'country.required' => 'Country is required',
            // 'state.required' => 'State is required',
            // 'city.required' => 'City is required',
            // 'email.required' => 'Email is required',
            'email.email' => 'Please enter a valid email address (e.g. name@example.com)',
            'alternativeEmail.email' => 'Please enter a valid alternate email address (e.g. name@example.com)',
            // 'alternativeEmail.required' => 'Alternative email is required',
            'mobile.digits' => 'Mobile number must be exactly 10 digits',
            // 'photo.required' => 'Photo is required',
            // 'document.required' => 'Document is required',
            // 'residence_address.required' => 'Residence address is required',
            // 'permanent_address.required' => 'Permanent address is required',

            // Qualification Details
            // 'degree.*.required' => 'Degree is required',
            // 'university_institution_name.*.required' => 'University/Institution name is required',
            // 'year_of_passing.*.required' => 'Year of passing is required',
            // 'percentage_CGPA.*.required' => 'Percentage/CGPA is required',
            // 'certificate.*.required' => 'Certificates are required',
            // 'certificate.*.mimes' => 'Certificates must be a file of type: pdf, jpg, jpeg, png.',

            // Experience Details
            // 'experience.*.required' => 'Experience is required',
            // 'specialization.*.required' => 'Specialization is required',
            // 'institution.*.required' => 'Institution is required',
            // 'position.*.required' => 'Position is required',
            // 'duration.*.required' => 'Duration is required',
            // 'work.*.required' => 'Work is required',


            // Bank Details
            // 'bankname.required' => 'Bank name is required',
            // 'accountnumber.required' => 'Account number is required',
            // 'ifsccode.required' => 'IFSC code is required',
            // 'pannumber.required' => 'PAN number is required',
            // 'pannumber.string' => 'PAN number must be a string',
            // 'pannumber.max' => 'PAN number must not exceed 10 characters',
            // 'pannumber.regex' => 'PAN number must be a valid format',
            // 'pannumber.unique' => 'PAN number must be unique',
            // 'pannumber.min' => 'PAN number must be at least 10 characters',

            // Other information
            // 'researchpublications.required' => 'Research publications are required',
            // 'professionalmemberships.required' => 'Professional memberships are required',
            // 'recommendationdetails.required' => 'Recommendation details are required',
            'joiningdate.required' => 'Joining date is required',
        ];
    }
}
