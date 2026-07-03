<?php

namespace App\Http\Controllers\FC;

use App\Http\Controllers\Controller;
use App\Models\FC\FcTravelArrivalSlot;
use App\Models\FC\StudentMaster;
use App\Models\FC\StudentMasterFirst;
use App\Models\FC\StudentTravelPlanMaster;
use App\Services\FC\FcRegistrationFlowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TravelPlanController extends Controller
{
    public function __construct(
        private FcRegistrationFlowService $registrationFlow
    ) {}

    public function show()
    {
        $userId = Auth::id();

        if (! $this->registrationFlow->isBankCompleteForTravel($userId)) {
            $form = $this->registrationFlow->activeFormFromSession();
            if ($form) {
                return redirect()->route('fc-reg.forms.dashboard', $form)
                    ->with('error', 'Please complete bank details before the travel plan.');
            }

            return redirect()->route('fc-reg.registration.bank')
                ->with('error', 'Please complete bank details before the travel plan.');
        }

        $plan = StudentTravelPlanMaster::forUser($userId)
            ->with(['fcArrivalSlot', 'legs.travelMode'])
            ->first();

        $slots = FcTravelArrivalSlot::query()
            ->where('is_active', true)
            ->whereNotNull('slot_date')
            ->orderBy('slot_date')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $availableDates = $slots
            ->pluck('slot_date')
            ->filter()
            ->map(fn ($d) => $d instanceof \Carbon\CarbonInterface ? $d->format('Y-m-d') : (string) $d)
            ->unique()
            ->values();

        $step1 = StudentMasterFirst::forUser($userId)->first();
        $master = StudentMaster::forUser($userId)->first();
        $rollS1 = trim((string) ($step1?->roll_no ?? ''));
        $rollSm = trim((string) ($master?->roll_no ?? ''));
        $displayCode = $rollS1 !== '' ? $step1?->roll_no : ($rollSm !== '' ? $master?->roll_no : null);

        $travelNav = $this->registrationFlow->travelViewContext($userId);

        $formStepNav = null;
        $form = $this->registrationFlow->activeFormFromSession();
        if ($form) {
            $this->registrationFlow->rememberActiveFormInSession($form);
            $formStepNav = $this->registrationFlow->buildTravelStepNav($form, $userId);
        }

        return view('fc.registration.travel', [
            'plan'           => $plan,
            'slots'          => $slots,
            'availableDates' => $availableDates,
            'step1'          => $step1,
            'master'         => $master,
            'displayCode'    => $displayCode,
            'travelNav'      => $travelNav,
            'formStepNav'    => $formStepNav,
        ]);
    }

    public function save(Request $request)
    {
        $userId = Auth::id();

        if (! $this->registrationFlow->isBankCompleteForTravel($userId)) {
            $form = $this->registrationFlow->activeFormFromSession();
            if ($form) {
                return redirect()->route('fc-reg.forms.dashboard', $form)
                    ->with('error', 'Please complete bank details first.');
            }

            return redirect()->route('fc-reg.registration.bank')
                ->with('error', 'Please complete bank details first.');
        }

        $existing = StudentTravelPlanMaster::forUser($userId)->first();
        if ($existing?->is_submitted) {
            return $this->registrationFlow->redirectAfterTravelSubmit(
                $userId,
                'Your travel plan is already submitted.'
            )->with('error', 'Your travel plan is already submitted and cannot be changed.');
        }

        $request->validate([
            'joining_date'              => 'required|date',
            'joining_time'              => 'nullable|date_format:H:i',
            'fc_travel_arrival_slot_id' => 'required|exists:fc_travel_arrival_slots,id',
            'mode_of_journey'           => 'required|string|in:By Air,By Road,By Train',
            'journey_vehicle_no'        => 'required|string|max:200',
            'academy_arrival_date'      => 'nullable|date',
            'arrival_time_dehradun'     => 'required|string|max:120',
            'require_academy_vehicle'   => 'required|boolean',
            'special_requirements'      => 'nullable|string|max:1000',
        ]);

        $slot = FcTravelArrivalSlot::where('id', $request->fc_travel_arrival_slot_id)
            ->where('is_active', true)
            ->first();
        if (! $slot) {
            return back()->withInput()->with('error', 'The selected time slot is not available.');
        }
        if (! $slot->slot_date || $slot->slot_date->format('Y-m-d') !== $request->joining_date) {
            return back()->withInput()->with('error', 'Please select a slot for the chosen arrival date.');
        }
        if (! $slot->hasRoomForUser($userId)) {
            return back()->withInput()->with('error', 'This time slot is full. Please pick another slot.');
        }

        $plan = StudentTravelPlanMaster::updateOrCreate(
            [fc_user_col('student_travel_plan_masters') => fc_user_val('student_travel_plan_masters', $userId)],
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
        StudentMaster::forUser($userId)->update(['travel_done' => 0]);

        return redirect()->route('fc-reg.registration.travel')
            ->with('success', 'Travel plan saved as draft. Review and submit when ready.');
    }

    public function submit(Request $request)
    {
        $userId = Auth::id();

        if (! $this->registrationFlow->isBankCompleteForTravel($userId)) {
            $form = $this->registrationFlow->activeFormFromSession();
            if ($form) {
                return redirect()->route('fc-reg.forms.dashboard', $form)
                    ->with('error', 'Please complete bank details first.');
            }

            return redirect()->route('fc-reg.registration.bank')
                ->with('error', 'Please complete bank details first.');
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

        $existing = StudentTravelPlanMaster::forUser($userId)->first();
        if ($existing?->is_submitted) {
            return $this->registrationFlow->redirectAfterTravelSubmit($userId);
        }

        $slot = FcTravelArrivalSlot::where('id', $request->fc_travel_arrival_slot_id)
            ->where('is_active', true)
            ->first();
        if (! $slot) {
            return back()->withInput()->with('error', 'The selected time slot is not available.');
        }
        if (! $slot->slot_date || $slot->slot_date->format('Y-m-d') !== $request->joining_date) {
            return back()->withInput()->with('error', 'Please select a slot for the chosen arrival date.');
        }
        if (! $slot->hasRoomForUser($userId)) {
            return back()->withInput()->with('error', 'The selected time slot is full. Please choose another slot.');
        }

        $plan = StudentTravelPlanMaster::updateOrCreate(
            [fc_user_col('student_travel_plan_masters') => fc_user_val('student_travel_plan_masters', $userId)],
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
                'is_submitted'              => true,
            ]
        );

        StudentMaster::forUser($userId)->update(['travel_done' => 1]);

        return $this->registrationFlow->redirectAfterTravelSubmit(
            $userId,
            'Travel plan submitted.'
        );
    }
}
