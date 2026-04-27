<?php

namespace App\Http\Controllers\FC;

use App\Http\Controllers\Controller;
use App\Models\FC\FcOtActivity;
use App\Models\FC\FcOtDetail;
use App\Services\FC\FcActivityService;
use Illuminate\View\View;

class FcActivityReportController extends Controller
{
    public function __construct(private FcActivityService $svc)
    {
    }

    public function summary(): View
    {
        $counts = $this->svc->getDeptCounts();
        return view('admin.fc-activities.reports.summary', compact('counts'));
    }

    public function byDepartment(string $dept): View
    {
        $deptMap = FcOtActivity::DEPT_ACTIVITY;
        abort_unless(isset($deptMap[$dept]), 404);
        $actCode = $deptMap[$dept];

        $ots = FcOtDetail::join('fc_otactivity_details', function ($join) use ($actCode) {
            $join->on('fc_ot_details.username', '=', 'fc_otactivity_details.username')
                ->where('fc_otactivity_details.activity', '=', $actCode)
                ->where('fc_otactivity_details.status', '=', 1);
        })
            ->select('fc_ot_details.otname', 'fc_ot_details.otcode', 'fc_ot_details.mobileno', 'fc_ot_details.service')
            ->orderBy('fc_ot_details.service')
            ->get();

        return view('admin.fc-activities.reports.by-department', compact('ots', 'dept', 'actCode'));
    }

    public function notJoined(): View
    {
        $allOts = FcOtDetail::active()->orderBy('otcode')->get();
        $joinedUsernames = FcOtActivity::where('activity', 'joined')->pluck('activityval', 'username')->toArray();

        $rows = $allOts->map(fn ($ot) => [
            'otname' => $ot->otname,
            'otcode' => $ot->otcode,
            'joined' => isset($joinedUsernames[$ot->username]),
        ]);

        $joinedCount = count($joinedUsernames);
        $notJoinedCount = $allOts->count() - $joinedCount;

        return view('admin.fc-activities.reports.not-joined', compact('rows', 'joinedCount', 'notJoinedCount'));
    }

    public function serviceWise(): View
    {
        $services = [
            'IPS', 'IAS', 'IRS(IT)', 'IRMS', 'IRS(CCE)', 'IDES', 'IFS', 'IFS(AIS)',
            'RBFC', 'IIS', 'IAAS', 'RBPS', 'IDAS', 'RBCS', 'ITS', 'IPTAFS', 'ICAS', 'IPOS', 'ICLS',
        ];
        $rawCounts = $this->svc->getServiceWiseCounts();
        $counts = [];
        foreach ($services as $svc) {
            $counts[$svc] = $rawCounts[$svc] ?? 0;
        }

        return view('admin.fc-activities.reports.service-wise', compact('counts', 'services'));
    }
}
