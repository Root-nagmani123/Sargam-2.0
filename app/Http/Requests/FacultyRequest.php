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

            // "facultyType" => "",
            // "firstName"=> "required|string|max:255|regex:/^[A-Za-z\s]+$/",
            // "middlename"=> "nullable|string|max:255|regex:/^[A-Za-z\s]+$/",
            // "lastname"=> "required|string|max:255|regex:/^[A-Za-z\s]+$/",
            // "fullname"=> "required|string|max:255|regex:/^[A-Za-z\s]+$/",
            // "gender"=>"required|string|in:male,female,other",
            // "landline"=> "required|string|max:255",
            // "mobile"=> "required|string|max:255",
            // "country"=> "required",
            // "state"=> "required|string",
            // "city"=> "required|string",
            // "email"=> "required|email|max:255",
            // "alternativeEmail" => "nullable|email|max:255",
            // 'photo' => [
            //     Rule::requiredIf(function () use ($existingFaculty) {
            //         return !$existingFaculty || empty($existingFaculty->photo_uplode_path);
            //     }),
            //     'nullable',
            //     'mimes:jpg,jpeg,png',
            //     'max:2048'
            // ],
            // "document" => [
            //     Rule::requiredIf(function () use ($existingFaculty) {
            //         return !$existingFaculty || empty($existingFaculty->Doc_uplode_path);
            //     }),
            //     'nullable',
            //     'mimes:pdf,jpg,jpeg,png',
            //     'max:2048'
            // ],
            // "residence_address" => "nullable|string|max:255",
            // "permanent_address" => "nullable|string|max:255",

            // Qualification Details (array fields)
            // 'degree.*' => 'required|string|max:255',
            // 'university_institution_name.*' => 'required|string|max:255',
            // 'year_of_passing.*' => 'required',
            // 'percentage_CGPA.*' => 'required|numeric|min:0|max:100',
            // 'certificate.*' => 'required|mimes:pdf,jpg,jpeg,png|max:2048',

            // Experience Details (array fields)
            // 'experience.*' => 'required|numeric|min:0|max:100',
            // 'specialization.*' => 'required|string|max:255',
            // 'institution.*' => 'required|string|max:255',
            // 'position.*' => 'required|string|max:255',
            // 'duration.*' => 'required|numeric|min:0|max:100',
            // 'work.*' => 'required|string|max:255',

            // Bank Details
            // "bankname" => "required|string|max:255",
            // "accountnumber" => "required|string|max:255",
            // "ifsccode" => "required|string|max:255",
            // "pannumber" => "required|string|max:255",
            

            // Other information
            // 'researchpublications' => 'nullable|mimes:pdf,jpg,jpeg|max:2048',
            // 'professionalmemberships' => 'nullable|mimes:pdf,jpg,jpeg|max:2048',
            // 'recommendationdetails' => 'nullable|mimes:pdf,jpg,jpeg|max:2048',
            // 'joiningdate'=> 'required|date',
            
            // Radio button
            // 'current_sector' => 'required|string|in:1,2',

            // 'other_city' => [
            //     Rule::requiredIf(in_array($this->input('city'), $otherCityPks)),
            //     'string',
            //     'nullable'
            // ],
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
            // 'alternativeEmail.required' => 'Alternative email is required',
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
            // 'joiningdate.required' => 'Joining date is required',
        ]; 
    }
}
