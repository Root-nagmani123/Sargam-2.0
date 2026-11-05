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
            'other_purpose' => ['nullable','required_if:purpose,Other', 'string', 'max:150'],
            'escort_required' => ['required', 'in:yes,no'],
            'is_faculty' => ['nullable', 'boolean'],

            // ---------------- Guest Details (shared, repeatable) ----------------
            'guests' => ['required', 'array', 'min:1'],
            'guests.*.guest_name' => ['required', 'string', 'max:150'],
            'guests.*.age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'guests.*.sex' => ['required', 'in:Male,Female'],
            'guests.*.designation' => ['nullable', 'string', 'max:150'],
            'guests.*.mobile_no' => ['required', 'regex:/^[6-9][0-9]{9}$/'],
            'guests.*.email_id' => ['nullable', 'email', 'max:150','regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', 'email:strict'],
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
            // ---------------- Request Types ----------------
            'types.required' => 'Please select at least one request type (Accommodation, Vehicle, or Ticket Booking).',
            'types.array' => 'Invalid request type selection.',
            'types.min' => 'Please select at least one request type.',
            'types.*.in' => 'Selected request type is invalid.',

            // ---------------- Shared Fields ----------------
            'course_team_to_notify.string' => 'Course Team to Notify must be a valid text value.',
            'course_team_to_notify.max' => 'Course Team to Notify cannot exceed 150 characters.',

            'purpose.required' => 'Please select a purpose.',
            'purpose.string' => 'Purpose must be a valid text value.',
            'purpose.max' => 'Purpose cannot exceed 150 characters.',

            'other_purpose.required_if' => 'Please specify the purpose when Other is selected.',
            'other_purpose.string' => 'Other Purpose must be a valid text value.',
            'other_purpose.max' => 'Other Purpose cannot exceed 150 characters.',

            'escort_required.required' => 'Please indicate whether an escort is required.',
            'escort_required.in' => 'Please select Yes or No for Escort Required.',

            'is_faculty.boolean' => 'Faculty status must be valid.',

            // ---------------- Guest Details ----------------
            'guests.required' => 'Please provide at least one guest.',
            'guests.array' => 'Guest details must be submitted in a valid format.',
            'guests.min' => 'Please add at least one guest.',

            'guests.*.guest_name.required' => 'Guest name is required.',
            'guests.*.guest_name.string' => 'Guest name must be valid text.',
            'guests.*.guest_name.max' => 'Guest name cannot exceed 150 characters.',

            'guests.*.age.integer' => 'Guest age must be a valid whole number.',
            'guests.*.age.min' => 'Guest age cannot be less than 0.',
            'guests.*.age.max' => 'Guest age cannot exceed 120.',

            'guests.*.sex.required' => 'Sex is required.',
            'guests.*.sex.in' => 'Please select Male or Female.',

            'guests.*.designation.string' => 'Designation must be valid text.',
            'guests.*.designation.max' => 'Designation cannot exceed 150 characters.',

            'guests.*.mobile_no.required' => 'Mobile number is required.',
            'guests.*.mobile_no.regex' => 'Mobile number is invalid. Enter 10 digits starting with 6, 7, 8, or 9.',

            'guests.*.email_id.email' => 'Please enter a valid email address.',
            'guests.*.email_id.email:strict' => 'Please enter a valid email address.',
            'guests.*.email_id.regex' => 'Email ID is invalid. Please enter an email in the correct format.',
            'guests.*.email_id.max' => 'Email address cannot exceed 150 characters.',

            'guests.*.guest_id_proof.string' => 'Guest ID Proof must be valid text.',
            'guests.*.guest_id_proof.max' => 'Guest ID Proof cannot exceed 150 characters.',

            'guests.*.is_main_guest.boolean' => 'Main Guest selection must be valid.',

            // ---------------- Guest House / Accommodation ----------------
            'guest_house.date_from.required' => 'Date From is required for the Accommodation request.',
            'guest_house.date_from.date' => 'Please enter a valid Accommodation start date.',
            'guest_house.date_from.after_or_equal' => 'Accommodation start date cannot be in the past.',

            'guest_house.date_to.required' => 'Date To is required for the Accommodation request.',
            'guest_house.date_to.date' => 'Please enter a valid Accommodation end date.',
            'guest_house.date_to.after' => 'Accommodation end date must be after the start date.',

            'guest_house.no_of_guests.integer' => 'Number of guests must be a valid whole number.',
            'guest_house.no_of_guests.min' => 'At least one guest must be specified.',
            'guest_house.no_of_guests.max' => 'Number of guests cannot exceed 50.',

            'guest_house.no_of_rooms.required' => 'Number of rooms is required for the Accommodation request.',
            'guest_house.no_of_rooms.integer' => 'Number of rooms must be a valid whole number.',
            'guest_house.no_of_rooms.min' => 'At least one room must be selected.',
            'guest_house.no_of_rooms.max' => 'Number of rooms cannot exceed 50.',

            'guest_house.remarks.string' => 'Accommodation remarks must be valid text.',
            'guest_house.remarks.max' => 'Accommodation remarks cannot exceed 1000 characters.',

            'guest_house.payment_done_by.string' => 'Payment Done By must be valid text.',
            'guest_house.payment_done_by.max' => 'Payment Done By cannot exceed 100 characters.',

            // ---------------- Vehicle ----------------
            'vehicle.vehicle_type.required' => 'Vehicle Type is required for the Vehicle request.',
            'vehicle.vehicle_type.string' => 'Please select a valid Vehicle Type.',
            'vehicle.vehicle_type.max' => 'Vehicle Type cannot exceed 100 characters.',

            'vehicle.one_way_booking.boolean' => 'One-way booking selection must be valid.',

            'vehicle.payment_will_be_done_by.string' => 'Vehicle Payment Done By must be valid text.',
            'vehicle.payment_will_be_done_by.max' => 'Vehicle Payment Done By cannot exceed 100 characters.',

            'vehicle.legs.required' => 'Please provide at least one travel leg for the Vehicle request.',
            'vehicle.legs.array' => 'Vehicle travel details must be submitted in a valid format.',
            'vehicle.legs.min' => 'Please add at least one travel leg.',

            'vehicle.legs.*.date_time_from.required' => 'Date/Time From is required for each travel leg.',
            'vehicle.legs.*.date_time_from.date' => 'Please enter a valid starting date and time for each travel leg.',

            'vehicle.legs.*.date_time_to.date' => 'Please enter a valid ending date and time for each travel leg.',
            'vehicle.legs.*.date_time_to.after_or_equal' => 'Date/Time To must be on or after Date/Time From.',

            'vehicle.legs.*.pickup_type.in' => 'Please select a valid Pickup Type.',
            'vehicle.legs.*.pickup_value.required' => 'Pickup location is required for each travel leg.',
            'vehicle.legs.*.pickup_value.string' => 'Pickup location must be valid text.',
            'vehicle.legs.*.pickup_value.max' => 'Pickup location cannot exceed 150 characters.',
            'vehicle.legs.*.pickup_time.date_format' => 'Pickup time must be in HH:MM format.',

            'vehicle.legs.*.drop_type.in' => 'Please select a valid Drop Type.',
            'vehicle.legs.*.drop_value.required' => 'Drop location is required for each travel leg.',
            'vehicle.legs.*.drop_value.string' => 'Drop location must be valid text.',
            'vehicle.legs.*.drop_value.max' => 'Drop location cannot exceed 150 characters.',
            'vehicle.legs.*.drop_time.date_format' => 'Drop time must be in HH:MM format.',

            'vehicle.legs.*.no_of_persons.integer' => 'Number of persons must be a valid whole number.',
            'vehicle.legs.*.no_of_persons.min' => 'At least one person must be specified.',
            'vehicle.legs.*.no_of_persons.max' => 'Number of persons cannot exceed 60.',

            'vehicle.legs.*.remarks.string' => 'Vehicle remarks must be valid text.',
            'vehicle.legs.*.remarks.max' => 'Vehicle remarks cannot exceed 1000 characters.',

            // ---------------- Ticket Booking ----------------
            'ticket.journeys.required' => 'Please provide at least one journey for the Ticket Booking request.',
            'ticket.journeys.array' => 'Ticket journey details must be submitted in a valid format.',
            'ticket.journeys.min' => 'Please add at least one journey.',

            'ticket.journeys.*.origin.required' => 'Origin is required for each journey.',
            'ticket.journeys.*.origin.string' => 'Journey Origin must be valid text.',
            'ticket.journeys.*.origin.max' => 'Journey Origin cannot exceed 150 characters.',

            'ticket.journeys.*.destination.required' => 'Destination is required for each journey.',
            'ticket.journeys.*.destination.string' => 'Journey Destination must be valid text.',
            'ticket.journeys.*.destination.max' => 'Journey Destination cannot exceed 150 characters.',

            'ticket.journeys.*.ticket_type.required' => 'Ticket Type is required for each journey.',
            'ticket.journeys.*.ticket_type.in' => 'Ticket Type must be Air, Rail, or Bus.',

            'ticket.journeys.*.class.string' => 'Ticket Class must be valid text.',
            'ticket.journeys.*.class.max' => 'Ticket Class cannot exceed 100 characters.',

            'ticket.journeys.*.date_of_journey.required' => 'Date of Journey is required for each journey.',
            'ticket.journeys.*.date_of_journey.date' => 'Please enter a valid Date of Journey.',
            'ticket.journeys.*.date_of_journey.after_or_equal' => 'Date of Journey cannot be in the past.',

            'ticket.journeys.*.train_flight_bus_name.required' => 'Train/Flight/Bus Name is required for each journey.',
            'ticket.journeys.*.train_flight_bus_name.string' => 'Train/Flight/Bus Name must be valid text.',
            'ticket.journeys.*.train_flight_bus_name.max' => 'Train/Flight/Bus Name cannot exceed 150 characters.',

            'ticket.journeys.*.train_flight_bus_no.required' => 'Train/Flight/Bus Number is required for each journey.',
            'ticket.journeys.*.train_flight_bus_no.string' => 'Train/Flight/Bus Number must be valid text.',
            'ticket.journeys.*.train_flight_bus_no.max' => 'Train/Flight/Bus Number cannot exceed 100 characters.',

            'ticket.journeys.*.quota.string' => 'Quota must be valid text.',
            'ticket.journeys.*.quota.max' => 'Quota cannot exceed 100 characters.',

            'ticket.journeys.*.book_if_waiting.boolean' => 'Book If Waiting selection must be valid.',

            'ticket.journeys.*.payment_will_be_done_by.string' => 'Ticket Payment Done By must be valid text.',
            'ticket.journeys.*.payment_will_be_done_by.max' => 'Ticket Payment Done By cannot exceed 100 characters.',

            'ticket.journeys.*.remarks.string' => 'Ticket remarks must be valid text.',
            'ticket.journeys.*.remarks.max' => 'Ticket remarks cannot exceed 1000 characters.',
        ];
    }
}
