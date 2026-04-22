<?php

namespace App\Http\Controllers\FC;

use App\Http\Controllers\Controller;
use App\Models\FC\MctpStudentTravelPlanDetail;
use App\Models\FC\MctpTravelModeMaster;
use App\Models\FC\PickUpDropTypeMaster;
use App\Models\FC\StudentMaster;
use App\Models\FC\StudentMasterFirst;
use App\Models\FC\StudentTravelPlanMaster;
use App\Models\FC\TravelTypeMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TravelPlanController extends Controller
{
    public function show()
    {
        $username = Auth::user()->username;

        if (! StudentMaster::where('username', $username)->value('bank_done')) {
            return redirect()->route('fc-reg.registration.bank')
                ->with('error', 'Please complete bank details before the travel plan.');
        }

        $plan = StudentTravelPlanMaster::where('username', $username)
            ->with(['travelType', 'pickupType', 'dropType', 'legs.travelMode'])
            ->first();

        $legs = $plan?->legs ?? collect();

        $legRows = old('legs');
        if ($legRows === null) {
            $legRows = $legs->isNotEmpty()
                ? $legs->map(fn ($l) => [
                    'from_city'           => $l->from_station,
                    'to_city'             => $l->to_station,
                    'travel_mode_id'      => $l->travel_mode_id,
                    'travel_date'         => $l->travel_date?->format('Y-m-d'),
                    'departure_time'      => $l->departure_time,
                    'arrival_time'        => $l->arrival_time,
                    'train_flight_bus_no' => $l->train_flight_no,
                    'train_flight_name'   => $l->train_flight_name,
                    'class_of_travel'     => $l->class_of_travel,
                    'ticket_no'           => $l->pnr_ticket_no,
                    'ticket_amount'       => $l->ticket_amount,
                    'remarks'             => $l->remarks,
                ])->values()->all()
                : [];
        }

        return view('fc.registration.travel', [
            'plan'        => $plan,
            'legRows'     => $legRows,
            'travelTypes' => TravelTypeMaster::query()->where('is_active', true)->orderBy('id')->get(),
            'travelModes' => MctpTravelModeMaster::query()->where('is_active', true)->orderBy('id')->get(),
            'pickupTypes' => PickUpDropTypeMaster::query()->where('is_active', true)->orderBy('id')->get(),
            'step1'       => StudentMasterFirst::where('username', $username)->first(),
            'master'      => StudentMaster::where('username', $username)->first(),
        ]);
    }

    public function save(Request $request)
    {
        $username = Auth::user()->username;

        if (! StudentMaster::where('username', $username)->value('bank_done')) {
            return redirect()->route('fc-reg.registration.bank')
                ->with('error', 'Please complete bank details first.');
        }

        $existing = StudentTravelPlanMaster::where('username', $username)->first();
        if ($existing?->is_submitted) {
            return redirect()->route('fc-reg.registration.documents')
                ->with('error', 'Your travel plan is already submitted and cannot be changed.');
        }

        $legsFiltered = collect($request->input('legs', []))
            ->filter(fn ($leg) => ! empty($leg['from_city'] ?? null) && ! empty($leg['to_city'] ?? null))
            ->values()
            ->all();
        $request->merge(['legs' => $legsFiltered]);

        $request->validate([
            'joining_date'         => 'required|date',
            'joining_time'         => 'nullable|date_format:H:i',
            'travel_type_id'       => 'required|exists:travel_type_masters,id',
            'departure_city'       => 'required|string|max:150',
            'departure_state'      => 'required|string|max:150',
            'needs_pickup'         => 'nullable|boolean',
            'pickup_type_id'       => 'nullable|exists:pick_up_drop_type_masters,id',
            'pickup_from_location' => 'nullable|string|max:200',
            'pickup_datetime'      => 'nullable|date',
            'needs_drop'           => 'nullable|boolean',
            'drop_type_id'         => 'nullable|exists:pick_up_drop_type_masters,id',
            'drop_to_location'     => 'nullable|string|max:200',
            'drop_datetime'        => 'nullable|date',
            'special_requirements' => 'nullable|string|max:1000',
            'legs'                 => 'nullable|array',
            'legs.*.from_city'     => 'required|string|max:150',
            'legs.*.to_city'       => 'required|string|max:150',
            'legs.*.travel_mode_id'=> 'required|exists:mctp_travel_mode_masters,id',
            'legs.*.travel_date'   => 'nullable|date',
            'legs.*.departure_time'=> 'nullable|date_format:H:i',
            'legs.*.arrival_time'  => 'nullable|date_format:H:i',
            'legs.*.train_flight_bus_no' => 'nullable|string|max:100',
            'legs.*.train_flight_name'   => 'nullable|string|max:200',
            'legs.*.class_of_travel'     => 'nullable|string|max:50',
            'legs.*.ticket_no'           => 'nullable|string|max:100',
            'legs.*.ticket_amount'       => 'nullable|numeric|min:0',
            'legs.*.remarks'             => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($request, $username) {
            $plan = StudentTravelPlanMaster::updateOrCreate(
                ['username' => $username],
                [
                    'joining_date'         => $request->joining_date,
                    'joining_time'         => $request->joining_time,
                    'travel_type_id'       => $request->travel_type_id,
                    'departure_city'       => $request->departure_city,
                    'departure_state'      => $request->departure_state,
                    'needs_pickup'         => $request->boolean('needs_pickup'),
                    'pickup_type_id'       => $request->pickup_type_id,
                    'pickup_from_location' => $request->pickup_from_location,
                    'pickup_datetime'      => $request->pickup_datetime,
                    'needs_drop'           => $request->boolean('needs_drop'),
                    'drop_type_id'         => $request->drop_type_id,
                    'drop_to_location'     => $request->drop_to_location,
                    'drop_datetime'        => $request->drop_datetime,
                    'special_requirements' => $request->special_requirements,
                    'is_submitted'         => false,
                ]
            );

            MctpStudentTravelPlanDetail::where('travel_plan_id', $plan->id)->delete();
            MctpStudentTravelPlanDetail::where('username', $username)->whereNull('travel_plan_id')->delete();

            foreach ($request->input('legs', []) as $legNo => $leg) {
                $mode = MctpTravelModeMaster::find($leg['travel_mode_id']);
                MctpStudentTravelPlanDetail::create([
                    'username'            => $username,
                    'travel_plan_id'      => $plan->id,
                    'leg_number'          => $legNo + 1,
                    'leg_no'              => (string) ($legNo + 1),
                    'from_station'        => $leg['from_city'],
                    'to_station'          => $leg['to_city'],
                    'travel_mode_id'      => $leg['travel_mode_id'],
                    'travel_mode'         => $mode?->travel_mode_name,
                    'travel_date'         => $leg['travel_date'] ?? null,
                    'departure_time'      => $leg['departure_time'] ?? null,
                    'arrival_time'        => $leg['arrival_time'] ?? null,
                    'train_flight_no'     => $leg['train_flight_bus_no'] ?? null,
                    'train_flight_name'   => $leg['train_flight_name'] ?? null,
                    'class_of_travel'     => $leg['class_of_travel'] ?? null,
                    'pnr_ticket_no'       => $leg['ticket_no'] ?? null,
                    'ticket_amount'       => $leg['ticket_amount'] ?? null,
                    'is_entitled'         => true,
                    'remarks'             => $leg['remarks'] ?? null,
                ]);
            }
        });

        return redirect()->route('fc-reg.registration.travel')
            ->with('success', 'Travel plan saved as draft. Add journey legs and submit when ready.');
    }

    public function submit(Request $request)
    {
        $username = Auth::user()->username;

        $plan = StudentTravelPlanMaster::where('username', $username)
            ->with('legs')
            ->first();

        if (! $plan || $plan->legs->isEmpty()) {
            return back()->with('error', 'Please save your travel plan with at least one journey leg before submitting.');
        }

        if ($plan->is_submitted) {
            return redirect()->route('fc-reg.registration.documents');
        }

        $plan->update(['is_submitted' => true]);
        StudentMaster::where('username', $username)->update(['travel_done' => 1]);

        return redirect()->route('fc-reg.registration.documents')
            ->with('success', 'Travel plan submitted. You may now upload documents.');
    }
}
