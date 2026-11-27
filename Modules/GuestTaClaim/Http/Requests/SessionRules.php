<?php

namespace Modules\GuestTaClaim\Http\Requests;

class SessionRules
{
    public static function rules(): array
    {
        return [
            'sessions' => ['required', 'array', 'min:1', 'max:100'],
            'sessions.*.source_session_id' => ['nullable', 'string', 'max:100'],
            'sessions.*.session_date' => ['required', 'date'],
            'sessions.*.start_time' => ['required', 'date_format:H:i'],
            'sessions.*.end_time' => ['required', 'date_format:H:i'],
            'sessions.*.topic' => ['required', 'string', 'max:255'],
            'sessions.*.venue' => ['nullable', 'string', 'max:120'],
            'sessions.*.status' => ['required', 'in:taken,cancelled'],
        ];
    }

    /** End must be after start on every row. */
    public static function after($validator, array $sessions): void
    {
        foreach ($sessions as $i => $s) {
            if (isset($s['start_time'], $s['end_time']) && $s['end_time'] <= $s['start_time']) {
                $validator->errors()->add("sessions.$i.end_time", 'Session '.($i + 1).': end time must be after the start time.');
            }
        }
    }
}
