<?php

namespace App\Http\Controllers\FC;

use App\Http\Controllers\Controller;
use App\Models\FC\SessionMaster;
use App\Models\FC\StudentMasterFirst;
use App\Models\FC\StudentTravelPlanMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TravelPlanReportController extends Controller
{
    public function index(Request $request)
    {
        $sessions = SessionMaster::orderByDesc('id')->get();

        $plans = DB::table('student_travel_plan_masters as tp')
            ->join('student_master_firsts as s1', 'tp.username', '=', 's1.username')
            ->leftJoin('service_masters as svc', 's1.service_id', '=', 'svc.id')
            ->leftJoin('travel_type_masters as tt', 'tp.travel_type_id', '=', 'tt.id')
            ->leftJoin('student_masters as sm', 'tp.username', '=', 'sm.username')
            ->when($request->session_id, fn ($q, $v) => $q->where('sm.session_id', $v))
            ->when($request->submitted, fn ($q, $v) => $q->where('tp.is_submitted', $v === 'yes' ? 1 : 0))
            ->when($request->filled('pickup'), fn ($q) => $q->where('tp.needs_pickup', 1))
            ->when($request->search, function ($q) use ($request) {
                $s = '%'.$request->search.'%';
                $q->where(fn ($qq) => $qq->where('tp.username', 'like', $s)->orWhere('s1.full_name', 'like', $s));
            })
            ->select(
                'tp.*',
                's1.full_name',
                's1.mobile_no',
                'svc.service_code',
                'tt.travel_type_name',
            )
            ->orderBy('s1.full_name')
            ->paginate(50)
            ->withQueryString();

        $summary = [
            'total'     => StudentTravelPlanMaster::count(),
            'submitted' => StudentTravelPlanMaster::where('is_submitted', 1)->count(),
            'pickup'    => StudentTravelPlanMaster::where('needs_pickup', 1)->count(),
            'drop'      => StudentTravelPlanMaster::where('needs_drop', 1)->count(),
        ];

        return view('admin.travel.index', compact('plans', 'sessions', 'summary'));
    }

    public function show(string $username)
    {
        $plan = StudentTravelPlanMaster::where('username', $username)
            ->with(['travelType', 'pickupType', 'dropType', 'legs.travelMode'])
            ->firstOrFail();

        $step1 = StudentMasterFirst::where('username', $username)
            ->with(['service', 'allottedState'])
            ->first();

        return view('admin.travel.show', compact('plan', 'step1', 'username'));
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
