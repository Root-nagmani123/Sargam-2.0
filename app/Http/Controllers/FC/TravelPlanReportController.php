<?php

namespace App\Http\Controllers\FC;

use App\DataTables\FC\FcTravelPlanReportDataTable;
use App\Http\Controllers\Controller;
use App\Models\FC\FcForm;
use App\Models\FC\FcTravelArrivalSlot;
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
        $forms = FcForm::query()
            ->with('courseMaster:pk,course_name')
            ->where('is_active', true)
            ->orderBy('form_name')
            ->get(['id', 'form_name', 'form_slug', 'course_master_pk']);
        $slots = FcTravelArrivalSlot::orderBy('sort_order')->orderBy('id')->get();
        $modes = ['By Air', 'By Road', 'By Train'];

        $summaryBase = FcTravelPlanReportService::baseQuery();
        FcTravelPlanReportService::applyFilters($summaryBase, $request);
        $summary = [
            'total'       => (clone $summaryBase)->count(),
            'submitted'   => (clone $summaryBase)->where('tp.is_submitted', 1)->count(),
            'vehicle_yes' => (clone $summaryBase)->where('tp.require_academy_vehicle', 1)->count(),
            'vehicle_no'  => (clone $summaryBase)->where(function ($q) {
                $q->where('tp.require_academy_vehicle', 0)->orWhereNull('tp.require_academy_vehicle');
            })->count(),
        ];

        return $dataTable->render('admin.travel.index', compact('forms', 'slots', 'modes', 'summary') + [
            'scopedForm' => $request->filled('form_id') ? FcForm::find((int) $request->input('form_id')) : null,
        ]);
    }

    public function show(int $userId)
    {
        $plan = StudentTravelPlanMaster::forUser($userId)
            ->with(['fcArrivalSlot'])
            ->firstOrFail();

        $step1 = StudentMasterFirst::forUser($userId)->first();
        $studentMaster = StudentMaster::forUser($userId)->first();

        $displayName = trim((string) ($step1?->full_name ?? '')) !== ''
            ? $step1->full_name
            : (trim((string) ($studentMaster?->full_name ?? '')) !== '' ? $studentMaster->full_name : (string) $userId);
        $displayMobile = $step1?->mobile_no;

        $rollS1 = trim((string) ($step1?->roll_no ?? ''));
        $rollSm = trim((string) ($studentMaster?->roll_no ?? ''));
        $displayCode = $rollS1 !== '' ? $step1?->roll_no : ($rollSm !== '' ? $studentMaster?->roll_no : null);

        return view('admin.travel.show', compact(
            'plan',
            'step1',
            'userId',
            'displayCode',
            'displayName',
            'displayMobile'
        ));
    }

    public function edit(int $userId)
    {
        $plan = StudentTravelPlanMaster::forUser($userId)->firstOrFail();
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
        $studentMaster = StudentMaster::forUser($userId)->first();
        $displayName = trim((string) ($step1?->full_name ?? ''))
            ?: (trim((string) ($studentMaster?->full_name ?? '')) ?: (string) $userId);

        return view('admin.travel.edit', compact(
            'plan',
            'userId',
            'slots',
            'availableDates',
            'displayName'
        ));
    }

    public function update(Request $request, int $userId)
    {
        $plan = StudentTravelPlanMaster::forUser($userId)->firstOrFail();

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
        if (! $slot->hasRoomForUser($userId)) {
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

        return redirect()->route('admin.travel.show', $userId)
            ->with('success', 'Travel plan updated successfully.');
    }

    public function exportJoiningReport(Request $request)
    {
        $q = FcTravelPlanReportService::baseQuery();
        FcTravelPlanReportService::applyFilters($q, $request);
        $rows = $q->orderByRaw("COALESCE(NULLIF(TRIM(s1.full_name), ''), NULLIF(TRIM(sm.full_name), ''), tp.user_id) ASC")
            ->get();

        $filterDescription = FcTravelPlanReportService::exportFilterDescription($request);
        $fileName = 'fc_travel_joining_'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(
            new FcTravelJoiningReportExport($rows, $filterDescription),
            $fileName
        );
    }

}
