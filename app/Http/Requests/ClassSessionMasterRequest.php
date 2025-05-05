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
        $this->merge([
            'start_time' => date('H:i:s', strtotime($this->start_time)),
            'end_time' => date('H:i:s', strtotime($this->end_time)),
        ]);
    }
    

}
