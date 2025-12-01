<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProgrammeRequest extends FormRequest
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
            'coursename' => 'required|string|max:255',
            'courseshortname' => 'required|string|max:255',
            'courseyear' => 'required|date_format:Y|integer|between:1900,2099', //in between 1900 and 2099
            'startdate' => 'required|date',
            'enddate' => 'required|date|after:startdate',
            'coursecoordinator' => 'required|string|max:255',
            'assistantcoursecoordinator' => 'required|array',
            'assistantcoursecoordinator.*' => 'required|string|max:255',
            'assistant_coordinator_role' => 'nullable|array',
            'assistant_coordinator_role.*' => 'nullable|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'coursename.required' => 'The course name is required.',
            'coursename.string' => 'The course name must be a string.',
            'coursename.max' => 'The course name must be less than 255 characters.',
            'courseshortname.required' => 'The course short name is required.',
            'courseshortname.string' => 'The course short name must be a string.',
            'courseshortname.max' => 'The course short name must be less than 255 characters.',
            'courseyear.required' => 'The course year is required.',
            'courseyear.date_format' => 'The course year must be a valid date.',
            'startdate.required' => 'The start date is required.',
            'startdate.date' => 'The start date must be a date.',
            'enddate.required' => 'The end date is required.',
            'enddate.date' => 'The end date must be a date.',
            'enddate.after' => 'The end date must be greater than the start date.',
            'coursecoordinator.required' => 'The course coordinator is required.',
            'coursecoordinator.string' => 'The course coordinator must be a string.',
            'coursecoordinator.max' => 'The course coordinator must be less than 255 characters.',
            'assistantcoursecoordinator.required' => 'The assistant course coordinator is required.',
            'assistantcoursecoordinator.array' => 'The assistant course coordinator must be an array.',
            'assistantcoursecoordinator.*.required' => 'Each assistant course coordinator is required.',
            'assistantcoursecoordinator.*.string' => 'Each assistant course coordinator must be a string.',
            'assistantcoursecoordinator.*.max' => 'Each assistant course coordinator must be less than 255 characters.',
            'assistant_coordinator_role.array' => 'The assistant coordinator role must be an array.',
            'assistant_coordinator_role.*.string' => 'Each assistant coordinator role must be a string.',
            'assistant_coordinator_role.*.max' => 'Each assistant coordinator role must be less than 255 characters.',
        ];
    }
}
