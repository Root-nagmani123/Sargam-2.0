<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClassSessionMasterRequest extends FormRequest
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
            'shift_name' => 'required',
            'start_time' => 'required',
            'end_time' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'shift_name.required' => 'Shift name is required.',
            'start_time.required' => 'Start time is required.',
            'end_time.required' => 'End time is required.',
        ];
    }
 
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->start_time >= $this->end_time) {
                $validator->errors()->add('end_time', 'End time must be greater than start time.');
            }
        });
    }
    
    public function prepareForValidation()
    {
        // Only normalise values that are actually there: strtotime('') is false
        // and date('H:i:s', false) is a real time, so a blank field used to
        // survive the `required` rule and save as 05:30:00.
        $normalise = function ($value) {
            if (blank($value)) {
                return $value;
            }

            $timestamp = strtotime($value);

            return $timestamp === false ? $value : date('H:i:s', $timestamp);
        };

        $this->merge([
            'start_time' => $normalise($this->start_time),
            'end_time' => $normalise($this->end_time),
        ]);
    }
    

}
