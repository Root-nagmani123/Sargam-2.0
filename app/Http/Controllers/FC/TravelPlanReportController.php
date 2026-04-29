<?php

namespace App\Http\Controllers\FC;

use App\DataTables\FC\FcTravelPlanReportDataTable;
use App\Http\Controllers\Controller;
use App\Models\FC\FcTravelArrivalSlot;
use App\Models\FC\SessionMaster;
use App\Models\FC\StudentMaster;
use App\Models\FC\StudentMasterFirst;
use App\Models\FC\StudentTravelPlanMaster;
use App\Exports\FcTravelJoiningReportExport;
use App\Services\FC\FcTravelPlanReportService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class TravelPlanReportController extends Controller
{
    public function index(Request $request, FcTravelPlanReportDataTable $dataTable)
    {
        $sessions = SessionMaster::orderByDesc('id')->get();
        $slots = FcTravelArrivalSlot::orderBy('sort_order')->orderBy('id')->get();
        $modes = ['By Air', 'By Road', 'By Train'];
        $summary = [
            'total'     => StudentTravelPlanMaster::count(),
            'submitted' => StudentTravelPlanMaster::where('is_submitted', 1)->count(),
            'vehicle_yes' => StudentTravelPlanMaster::where('require_academy_vehicle', 1)->count(),
            'vehicle_no'  => StudentTravelPlanMaster::where(function ($q) {
                $q->where('require_academy_vehicle', 0)->orWhereNull('require_academy_vehicle');
            })->count(),
        ];

        return $dataTable->render('admin.travel.index', compact('sessions', 'slots', 'modes', 'summary'));
    }

    public function show(string $username)
    {
        $plan = StudentTravelPlanMaster::where('username', $username)
            ->with(['fcArrivalSlot'])
            ->firstOrFail();

        $step1 = StudentMasterFirst::where('username', $username)->first();
        $studentMaster = StudentMaster::where('username', $username)->first();

        $displayName = trim((string) ($step1?->full_name ?? '')) !== ''
            ? $step1->full_name
            : (trim((string) ($studentMaster?->full_name ?? '')) !== '' ? $studentMaster->full_name : $username);
        $displayMobile = $step1?->mobile_no;

        $rollS1 = trim((string) ($step1?->roll_no ?? ''));
        $rollSm = trim((string) ($studentMaster?->roll_no ?? ''));
        $displayCode = $rollS1 !== '' ? $step1?->roll_no : ($rollSm !== '' ? $studentMaster?->roll_no : null);

        return view('admin.travel.show', compact(
            'plan',
            'step1',
            'username',
            'displayCode',
            'displayName',
            'displayMobile'
        ));
    }

    public function edit(string $username)
    {
        $plan = StudentTravelPlanMaster::where('username', $username)->firstOrFail();
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

        $step1 = StudentMasterFirst::where('username', $username)->first();
        $studentMaster = StudentMaster::where('username', $username)->first();
        $displayName = trim((string) ($step1?->full_name ?? ''))
            ?: (trim((string) ($studentMaster?->full_name ?? '')) ?: $username);

        return view('admin.travel.edit', compact(
            'plan',
            'username',
            'slots',
            'availableDates',
            'displayName'
        ));
    }

    public function update(Request $request, string $username)
    {
        $plan = StudentTravelPlanMaster::where('username', $username)->firstOrFail();

        $validated = $request->validate([
            'joining_date'              => 'required|date',
            'fc_travel_arrival_slot_id' => 'required|exists:fc_travel_arrival_slots,id',
            'mode_of_journey'           => 'required|string|in:By Air,By Road,By Train',
            'journey_vehicle_no'        => 'required|string|max:200',
            'arrival_time_dehradun'     => 'required|string|max:120',
            'require_academy_vehicle'   => 'required|boolean',
            'special_requirements'      => 'nullable|string|max:1000',
        ]);

        $slot = FcTravelArrivalSlot::where('id', $validated['fc_travel_arrival_slot_id'])
            ->where('is_active', true)
            ->first();
        if (! $slot) {
            return back()->withInput()->with('error', 'The selected time slot is not available.');
        }
        if (! $slot->slot_date || $slot->slot_date->format('Y-m-d') !== $validated['joining_date']) {
            return back()->withInput()->with('error', 'Please select a slot for the chosen arrival date.');
        }
        if (! $slot->hasRoomForUser($username)) {
            return back()->withInput()->with('error', 'This time slot is full. Please pick another slot.');
        }

        $plan->update([
            'joining_date'              => $validated['joining_date'],
            'fc_travel_arrival_slot_id' => $validated['fc_travel_arrival_slot_id'],
            'mode_of_journey'           => $validated['mode_of_journey'],
            'journey_vehicle_no'        => $validated['journey_vehicle_no'],
            'arrival_time_dehradun'     => $validated['arrival_time_dehradun'],
            'require_academy_vehicle'   => $request->boolean('require_academy_vehicle'),
            'special_requirements'      => $validated['special_requirements'] ?? null,
        ]);

        return redirect()->route('admin.travel.show', $username)
            ->with('success', 'Travel plan updated successfully.');
    }

    public function exportJoiningReport(Request $request)
    {
        $q = FcTravelPlanReportService::baseQuery();
        FcTravelPlanReportService::applyFilters($q, $request);
        $rows = $q->orderByRaw("COALESCE(NULLIF(TRIM(s1.full_name), ''), NULLIF(TRIM(sm.full_name), ''), tp.username)")->get();

        $filterDescription = FcTravelPlanReportService::exportFilterDescription($request);
        $fileName = 'fc_travel_joining_'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(
            new FcTravelJoiningReportExport($rows, $filterDescription),
            $fileName
        );
    }

}
