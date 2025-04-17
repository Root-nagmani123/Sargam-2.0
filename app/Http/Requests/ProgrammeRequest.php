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
            'courseyear' => 'required|date_format:Y-m',
            'startdate' => 'required|date',
            'enddate' => 'required|date',
            'coursecoordinator' => 'required|string|max:255',
            'assistantcoursecoordinator' => 'required|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'coursename.required' => 'The course name is required.',
            'coursename.string' => 'The course name must be a string.',
            'coursename.max' => 'The course name must be less than 255 characters.',
            'courseyear.required' => 'The course year is required.',
            'courseyear.date_format' => 'The course year must be a valid date.',
            'startdate.required' => 'The start date is required.',
            'startdate.date' => 'The start date must be a date.',
            'enddate.required' => 'The end date is required.',
            'enddate.date' => 'The end date must be a date.',
            'coursecoordinator.required' => 'The course coordinator is required.',
            'coursecoordinator.string' => 'The course coordinator must be a string.',
            'coursecoordinator.max' => 'The course coordinator must be less than 255 characters.',
            'assistantcoursecoordinator.required' => 'The assistant course coordinator is required.',
            'assistantcoursecoordinator.string' => 'The assistant course coordinator must be a string.',
            'assistantcoursecoordinator.max' => 'The assistant course coordinator must be less than 255 characters.',
        ];
    }
}
