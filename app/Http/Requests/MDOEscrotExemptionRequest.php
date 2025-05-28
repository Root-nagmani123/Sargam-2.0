<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MDOEscrotExemptionRequest extends FormRequest
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
            'course_master_pk' => 'required|exists:course_master,pk',
            'mdo_duty_type_master_pk' => 'required|exists:mdo_duty_type_master,pk',
            'mdo_date' => 'required|date',
            'Time_from' => 'required|date_format:H:i',
            'Time_to' => 'required|date_format:H:i|after:Time_from',
            'Remark' => 'nullable|string|max:255',
            'selected_student_list' => 'required|array',
        ];
    }

    public function messages()
    {
        return [
            'course_master_pk.required' => 'The course field is required.',
            'mdo_duty_type_master_pk.required' => 'The MDO duty type field is required.',
            'mdo_date.required' => 'The MDO date field is required.',
            'Time_from.required' => 'The time from field is required.',
            'Time_to.required' => 'The time to field is required.',
            'Time_to.after' => 'The time to must be after the time from.',
            'selected_student_list.required' => 'Please select at least one student.',
            'Remark.string' => 'The remark must be a string.',
            'Remark.max' => 'The remark may not be greater than 255 characters.',
        ];
    }
}
