<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\MDOEscotDutyMap;

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
     * Faculty may arrive as a single value (legacy form) or an array (multi-select).
     * Normalise to an array so the rules and controller always see a list.
     */
    protected function prepareForValidation()
    {
        if ($this->has('faculty_master_pk') && !is_array($this->faculty_master_pk)) {
            $value = $this->faculty_master_pk;
            $this->merge([
                'faculty_master_pk' => ($value === null || $value === '') ? [] : [$value],
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $rules = [
            'course_master_pk' => 'required|exists:course_master,pk',
            'mdo_duty_type_master_pk' => 'required|exists:mdo_duty_type_master,pk',
            'mdo_date' => 'required|date',
            'Time_from' => 'required|date_format:H:i',
            'Time_to' => 'required|date_format:H:i|after:Time_from',
            'Remark' => 'nullable|string|max:255',
            'selected_student_list' => 'required|array',
            // Faculty can be multiple (one or more). Accept an array of faculty pks.
            'faculty_master_pk' => 'nullable|array',
            'faculty_master_pk.*' => 'exists:faculty_master,pk',
        ];

        $dutyTypes = MDOEscotDutyMap::getMdoDutyTypes();

        // If duty type is Escort, at least one faculty is required.
        $escortDutyTypeId = $dutyTypes['escort'] ?? null;
        if ($this->mdo_duty_type_master_pk == $escortDutyTypeId) {
            $rules['faculty_master_pk'] = 'required|array|min:1';
            $rules['faculty_master_pk.*'] = 'exists:faculty_master,pk';
        }

        // If duty type is Other, the free-text duty name is required.
        $otherDutyTypeId = $dutyTypes['other'] ?? null;
        if ($otherDutyTypeId && $this->mdo_duty_type_master_pk == $otherDutyTypeId) {
            $rules['duty_other'] = 'required|string|max:255';
        } else {
            $rules['duty_other'] = 'nullable|string|max:255';
        }

        return $rules;
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
            'faculty_master_pk.required' => 'The faculty field is required when Duty Type is Escort.',
            'faculty_master_pk.min' => 'The faculty field is required when Duty Type is Escort.',
            'faculty_master_pk.*.exists' => 'The selected faculty is invalid.',
            'duty_other.required' => 'The duty other field is required when Duty Type is Other.',
            'duty_other.max' => 'The duty other may not be greater than 255 characters.',
        ];
    }
}
