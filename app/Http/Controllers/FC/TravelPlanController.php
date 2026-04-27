<?php

namespace App\Http\Controllers\FC;

use App\Http\Controllers\Controller;
use App\Models\FC\FcTravelArrivalSlot;
use App\Models\FC\StudentMaster;
use App\Models\FC\StudentMasterFirst;
use App\Models\FC\StudentTravelPlanMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            ->with(['fcArrivalSlot', 'legs.travelMode', 'pickupType', 'dropType'])
            ->first();

        $slots = FcTravelArrivalSlot::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $step1 = StudentMasterFirst::where('username', $username)->first();
        $master = StudentMaster::where('username', $username)->first();
        $rollS1 = trim((string) ($step1?->roll_no ?? ''));
        $rollSm = trim((string) ($master?->roll_no ?? ''));
        $displayCode = $rollS1 !== '' ? $step1?->roll_no : ($rollSm !== '' ? $master?->roll_no : null);

        return view('fc.registration.travel', [
            'plan'         => $plan,
            'slots'        => $slots,
            'step1'        => $step1,
            'master'       => $master,
            'displayCode'  => $displayCode,
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

        $request->validate([
            'joining_date'              => 'required|date',
            'joining_time'              => 'nullable|date_format:H:i',
            'fc_travel_arrival_slot_id' => 'required|exists:fc_travel_arrival_slots,id',
            'mode_of_journey'           => 'required|string|in:By Air,By Road,By Train',
            'journey_vehicle_no'        => 'nullable|string|max:200',
            'academy_arrival_date'      => 'nullable|date',
            'arrival_time_dehradun'     => 'nullable|string|max:120',
            'require_academy_vehicle'   => 'nullable|boolean',
            'special_requirements'      => 'nullable|string|max:1000',
        ]);

        $slot = FcTravelArrivalSlot::where('id', $request->fc_travel_arrival_slot_id)
            ->where('is_active', true)
            ->first();
        if (! $slot) {
            return back()->withInput()->with('error', 'The selected time slot is not available.');
        }
        if (! $slot->hasRoomForUser($username)) {
            return back()->withInput()->with('error', 'This time slot is full. Please pick another slot.');
        }

        $plan = StudentTravelPlanMaster::updateOrCreate(
            ['username' => $username],
            [
                'joining_date'              => $request->joining_date,
                'joining_time'              => $request->joining_time,
                'fc_travel_arrival_slot_id' => $request->fc_travel_arrival_slot_id,
                'mode_of_journey'           => $request->mode_of_journey,
                'journey_vehicle_no'        => $request->journey_vehicle_no,
                'academy_arrival_date'      => $request->academy_arrival_date,
                'arrival_time_dehradun'     => $request->arrival_time_dehradun,
                'require_academy_vehicle'   => $request->boolean('require_academy_vehicle'),
                'special_requirements'      => $request->special_requirements,
                'is_submitted'              => false,
            ]
        );

        // Ensure draft is never treated as completed in FC pipeline (e.g. travel_done back-filled from docs_done).
        $plan->forceFill(['is_submitted' => false])->save();
        StudentMaster::where('username', $username)->update(['travel_done' => 0]);

        return redirect()->route('fc-reg.registration.travel')
            ->with('success', 'Travel plan saved as draft. Review and submit when ready.');
    }

    public function submit(Request $request)
    {
        $username = Auth::user()->username;

        $plan = StudentTravelPlanMaster::where('username', $username)->first();

        if (! $plan) {
            return back()->with('error', 'Please save your travel plan as draft first.');
        }

        if ($plan->is_submitted) {
            return redirect()->route('fc-reg.registration.documents');
        }

        if (! $plan->fc_travel_arrival_slot_id
            || ! $plan->joining_date
            || ! $plan->mode_of_journey) {
            return back()->with('error', 'Please complete required fields (arrival date, slot, and mode of journey) and save as draft before submitting.');
        }

        $slot = FcTravelArrivalSlot::find($plan->fc_travel_arrival_slot_id);
        if ($slot && ! $slot->hasRoomForUser($username)) {
            return back()->with('error', 'The selected time slot is full. Please change your slot and save again.');
        }

        $plan->update(['is_submitted' => true]);
        StudentMaster::where('username', $username)->update(['travel_done' => 1]);

        return redirect()->route('fc-reg.registration.documents')
            ->with('success', 'Travel plan submitted. You may now upload documents.');
    }
}
