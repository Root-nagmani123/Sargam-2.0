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
use Illuminate\Support\Facades\DB;
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
            'pickup'    => StudentTravelPlanMaster::where('needs_pickup', 1)->count(),
            'drop'      => StudentTravelPlanMaster::where('needs_drop', 1)->count(),
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

    public function exportPickup()
    {
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename=pickup_list_'.now()->format('Ymd').'.csv',
        ];

        return response()->stream(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['#', 'Username', 'Full Name', 'Service', 'Pickup From', 'Pickup Date/Time', 'Mobile']);

            $rows = DB::table('student_travel_plan_masters as tp')
                ->join('student_master_firsts as s1', 'tp.username', '=', 's1.username')
                ->leftJoin('service_masters as svc', 's1.service_id', '=', 'svc.id')
                ->where('tp.needs_pickup', 1)
                ->where('tp.is_submitted', 1)
                ->orderBy('tp.pickup_datetime')
                ->select(
                    'tp.username',
                    'tp.pickup_from_location',
                    'tp.pickup_datetime',
                    's1.full_name',
                    's1.mobile_no',
                    'svc.service_code'
                )
                ->get();

            foreach ($rows as $i => $r) {
                fputcsv($out, [
                    $i + 1,
                    $r->username,
                    $r->full_name,
                    $r->service_code ?? '',
                    $r->pickup_from_location ?? '',
                    $r->pickup_datetime ?? '',
                    $r->mobile_no,
                ]);
            }
            fclose($out);
        }, 200, $headers);
    }
}
