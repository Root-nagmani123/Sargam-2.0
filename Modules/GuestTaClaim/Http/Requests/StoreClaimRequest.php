<?php

namespace Modules\GuestTaClaim\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $att = config('guest-ta-claim.attachments');

        return array_merge([
            'course_id' => [$this->isMethod('post') ? 'required' : 'sometimes', 'string', 'max:64'],
            'faculty_id' => [$this->isMethod('post') ? 'required' : 'sometimes', 'string', 'max:64'],
            'period_from' => [$this->isMethod('post') ? 'required' : 'sometimes', 'date'],
            'period_to' => [$this->isMethod('post') ? 'required' : 'sometimes', 'date', 'after_or_equal:period_from'],
            'faculty_category' => ['required', Rule::in(array_keys(config('guest-ta-claim.honorarium_rates')))],
            'honorarium_rate' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'faculty_official_address' => ['nullable', 'string', 'max:2000'],
            'basic_pay' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
            'travelling_allowances' => ['nullable', 'array', 'max:30'],
            'travelling_allowances.*' => ['array'],
            'travelling_allowances.*.departure_at' => ['nullable', 'date'],
            'travelling_allowances.*.departure_from' => ['nullable', 'string', 'max:200'],
            'travelling_allowances.*.arrival_at' => ['nullable', 'date'],
            'travelling_allowances.*.arrival_to' => ['nullable', 'string', 'max:200'],
            'travelling_allowances.*.travel_mode' => ['nullable', Rule::in(array_merge(config('guest-ta-claim.travel_modes'), ['Other']))],
            'travelling_allowances.*.distance_km' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'travelling_allowances.*.fare_paid' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'travelling_allowances.*.fare_admissible' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'travelling_allowances.*.accommodation' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'da_journey_days' => ['nullable', 'integer', 'min:0', 'max:'.config('guest-ta-claim.max_da_days', 30)],
            'da_stay_days' => ['nullable', 'integer', 'min:0', 'max:'.config('guest-ta-claim.max_da_days', 30)],
            'da_rate' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'da_percentage' => ['required', Rule::in([70, 100])],
            'da_rate_basis' => [$this->isMethod('post') ? 'required' : 'nullable', Rule::in(['old', 'revised'])],
            'admissible_journey_numbers' => ['nullable', 'string', 'max:500'],
            'higher_class_journey_numbers' => ['nullable', 'string', 'max:500'],
            'higher_class_reimbursement_basis' => ['nullable', Rule::in(['admissible_rate', 'actual_fare_public_interest'])],
            'sessions_confirmed' => ['required', 'accepted'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'mimes:'.implode(',', $att['mimes']), 'max:'.$att['max_kb']],
            'action' => ['required', 'in:draft,forward'],
        ], SessionRules::rules());
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            SessionRules::after($v, (array) $this->input('sessions', []));
            $taken = collect((array) $this->input('sessions', []))->where('status', 'taken')->count();
            if ($taken === 0) {
                $v->errors()->add('sessions', 'Mark at least one session as taken.');
            }

            $journeyDays = (int) $this->input('da_journey_days', 0);
            $stayDays = (int) $this->input('da_stay_days', 0);
            if ($journeyDays + $stayDays > 0 && ! $this->filled('da_rate')) {
                $v->errors()->add('da_rate', 'Enter the DA rate when DA days are claimed.');
            }

            foreach ((array) $this->input('travelling_allowances', []) as $index => $row) {
                if (! is_array($row)) {
                    continue;
                }

                if (! collect((array) $row)->contains(fn ($value) => $value !== null && $value !== '' && $value !== '0')) {
                    continue;
                }

                foreach (['departure_at', 'departure_from', 'arrival_at', 'arrival_to', 'travel_mode'] as $field) {
                    if (empty($row[$field])) {
                        $v->errors()->add("travelling_allowances.$index.$field", 'Complete the departure and arrival details for each journey.');
                    }
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'sessions_confirmed.accepted' => 'Confirm the session details before saving or forwarding the claim.',
        ];
    }
}
