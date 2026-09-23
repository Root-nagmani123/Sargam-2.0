<?php

namespace Modules\Protocol\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the combined "Request for Accommodation / Vehicle / Tickets"
 * form. Shared fields (Course Team To Notify, Purpose, Escort Required,
 * Faculty, and the repeatable Guest Details table) are always validated.
 * The three type sections (Guest House / Vehicle / Ticket) are each
 * arrays of repeatable rows, and are only required for whichever types
 * were checked in `types[]`.
 */
class StoreCombinedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $types = (array) $this->input('types', []);

        $hasGuestHouse = in_array('guesthouse', $types, true);
        $hasVehicle = in_array('vehicle', $types, true);
        $hasTicket = in_array('ticket', $types, true);

        return [
            // ---------------- Shared / top-of-form fields ----------------
            'types' => ['required', 'array', 'min:1'],
            'types.*' => ['in:guesthouse,vehicle,ticket'],

            'course_team_to_notify' => ['nullable', 'string', 'max:150'],
            'purpose' => ['required', 'string', 'max:150'],
            'escort_required' => ['required', 'in:yes,no'],
            'is_faculty' => ['nullable', 'boolean'],

            // ---------------- Guest Details (shared, repeatable) ----------------
            'guests' => ['required', 'array', 'min:1'],
            'guests.*.guest_name' => ['required', 'string', 'max:150'],
            'guests.*.age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'guests.*.sex' => ['required', 'in:Male,Female'],
            'guests.*.designation' => ['nullable', 'string', 'max:150'],
            'guests.*.mobile_no' => ['required', 'string', 'max:20'],
            'guests.*.email_id' => ['nullable', 'email', 'max:150'],
            'guests.*.guest_id_proof' => ['nullable', 'string', 'max:150'],
            'guests.*.is_main_guest' => ['nullable', 'boolean'],

            // ---------------- Guest House section ----------------
            'guest_house.date_from' => [Rule::requiredIf($hasGuestHouse), 'nullable', 'date', 'after_or_equal:today'],
            'guest_house.date_to' => [Rule::requiredIf($hasGuestHouse), 'nullable', 'date', 'after:guest_house.date_from'],
            'guest_house.no_of_guests' => ['nullable', 'integer', 'min:1', 'max:50'],
            'guest_house.no_of_rooms' => [Rule::requiredIf($hasGuestHouse), 'nullable', 'integer', 'min:1', 'max:50'],
            'guest_house.remarks' => ['nullable', 'string', 'max:1000'],
            'guest_house.payment_done_by' => ['nullable', 'string', 'max:100'],

            // ---------------- Vehicle section ----------------
            'vehicle.vehicle_type' => [Rule::requiredIf($hasVehicle), 'nullable', 'string', 'max:100'],
            'vehicle.one_way_booking' => ['nullable', 'boolean'],
            'vehicle.payment_will_be_done_by' => ['nullable', 'string', 'max:100'],

            'vehicle.legs' => [Rule::requiredIf($hasVehicle), 'nullable', 'array', 'min:1'],
            'vehicle.legs.*.date_time_from' => [Rule::requiredIf($hasVehicle), 'nullable', 'date'],
            'vehicle.legs.*.date_time_to' => ['nullable', 'date', 'after_or_equal:vehicle.legs.*.date_time_from'],
            'vehicle.legs.*.pickup_type' => ['nullable', 'in:location,reference'],
            'vehicle.legs.*.pickup_value' => [Rule::requiredIf($hasVehicle), 'nullable', 'string', 'max:150'],
            'vehicle.legs.*.pickup_time' => ['nullable', 'date_format:H:i'],
            'vehicle.legs.*.drop_type' => ['nullable', 'in:location,reference'],
            'vehicle.legs.*.drop_value' => [Rule::requiredIf($hasVehicle), 'nullable', 'string', 'max:150'],
            'vehicle.legs.*.drop_time' => ['nullable', 'date_format:H:i'],
            'vehicle.legs.*.no_of_persons' => ['nullable', 'integer', 'min:1', 'max:60'],
            'vehicle.legs.*.remarks' => ['nullable', 'string', 'max:1000'],

            // ---------------- Ticket section ----------------
            'ticket.journeys' => [Rule::requiredIf($hasTicket), 'nullable', 'array', 'min:1'],
            'ticket.journeys.*.origin' => [Rule::requiredIf($hasTicket), 'nullable', 'string', 'max:150'],
            'ticket.journeys.*.destination' => [Rule::requiredIf($hasTicket), 'nullable', 'string', 'max:150'],
            'ticket.journeys.*.ticket_type' => [Rule::requiredIf($hasTicket), 'nullable', 'in:Air,Rail,Bus'],
            'ticket.journeys.*.class' => ['nullable', 'string', 'max:100'],
            'ticket.journeys.*.date_of_journey' => [Rule::requiredIf($hasTicket), 'nullable', 'date', 'after_or_equal:today'],
            'ticket.journeys.*.train_flight_bus_name' => [Rule::requiredIf($hasTicket), 'nullable', 'string', 'max:150'],
            'ticket.journeys.*.train_flight_bus_no' => [Rule::requiredIf($hasTicket), 'nullable', 'string', 'max:100'],
            'ticket.journeys.*.quota' => ['nullable', 'string', 'max:100'],
            'ticket.journeys.*.book_if_waiting' => ['nullable', 'boolean'],
            'ticket.journeys.*.payment_will_be_done_by' => ['nullable', 'string', 'max:100'],
            'ticket.journeys.*.remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'types.required' => 'Please select at least one request type (Accommodation, Vehicle, or Ticket Booking).',
            'purpose.required' => 'Please select a purpose.',
            'escort_required.required' => 'Please indicate whether an escort is required.',
            'guests.required' => 'Please provide at least one guest.',
            'guests.*.guest_name.required' => 'Guest name is required.',
            'guests.*.mobile_no.required' => 'Mobile number is required.',
            'guests.*.sex.required' => 'Sex is required.',
            'guest_house.date_from.required_if' => 'Date From is required for the Accommodation request.',
            'guest_house.date_to.required_if' => 'Date To is required for the Accommodation request.',
            'guest_house.no_of_rooms.required_if' => 'No. of Rooms is required for the Accommodation request.',
            'vehicle.vehicle_type.required_if' => 'Vehicle Type is required for the Vehicle request.',
            'vehicle.legs.required_if' => 'Please provide at least one travel leg for the Vehicle request.',
            'ticket.journeys.required_if' => 'Please provide at least one journey for the Ticket Booking request.',
        ];
    }
}
