<?php

namespace App\Http\Controllers\FC;

use App\Http\Controllers\Controller;
use App\Models\FC\FcActivityMaster;
use App\Models\FC\FcOtActivity;
use App\Models\FC\FcOtDetail;
use App\Services\FC\FcActivityService;
use App\Services\FC\FcPostArrivalAccessService;
use Illuminate\View\View;

class FcActivityReportController extends Controller
{
    public function __construct(
        private FcActivityService $svc,
        private FcPostArrivalAccessService $access
    ) {
    }

    public function summary(): View
    {
        $counts = $this->svc->getVisibleActivityCounts();

        $departments = \App\Models\FC\FcActivityDepartment::query()
            ->active()
            ->ordered()
            ->with([
                'masters' => fn ($q) => $q->where('status', 1)->ordered(),
            ])
            ->get();

        return view('admin.fc-activities.reports.summary', compact('departments', 'counts'));
    }

    public function byActivity(string $menuid): View
    {
        $this->access->assertMenuidAllowedForUser($menuid);

        $ots = FcOtDetail::query()
            ->join('fc_otactivity_details', function ($join) use ($menuid) {
                $join->on('fc_ot_details.user_id', '=', 'fc_otactivity_details.user_id')
                    ->where('fc_otactivity_details.activity', '=', $menuid)
                    ->where('fc_otactivity_details.status', '=', 1);
            })
            ->select(
                'fc_ot_details.otname',
                'fc_ot_details.otcode',
                'fc_ot_details.mobileno',
                'fc_ot_details.service'
            )
            ->orderBy('fc_ot_details.service')
            ->get();

        $label = FcActivityMaster::query()->where('menuid', $menuid)->value('menun');

        return view('admin.fc-activities.reports.by-activity', [
            'ots' => $ots,
            'menuid' => $menuid,
            'label' => $label ?? $menuid,
        ]);
    }

    public function notJoined(): View
    {
        $joinedCode = FcActivityMaster::joinedMarkerMenuid();
        if (! $joinedCode) {
            $rows = collect();
            $joinedCount = 0;
            $notJoinedCount = 0;

            return view('admin.fc-activities.reports.not-joined', [
                'rows' => $rows,
                'joinedCount' => $joinedCount,
                'notJoinedCount' => $notJoinedCount,
                'warning' => 'Configure one activity as joined marker in activity master.',
            ]);
        }

        $allOts = FcOtDetail::active()->orderBy('otcode')->get();
        $joinedUsernameSet = FcOtActivity::query()
            ->where('activity', $joinedCode)
            ->where('status', 1)
            ->distinct()
            ->pluck('user_id')
            ->flip()
            ->all();

        $rows = $allOts->map(fn ($ot) => [
            'otname' => $ot->otname,
            'otcode' => $ot->otcode,
            'joined' => isset($joinedUsernameSet[$ot->user_id]),
        ]);

        $joinedCount = count($joinedUsernameSet);
        $notJoinedCount = max(0, $allOts->count() - $joinedCount);

        return view(
            'admin.fc-activities.reports.not-joined',
            compact('rows', 'joinedCount', 'notJoinedCount')
        );
    }

    public function serviceWise(): View
    {
        $joinedCode = FcActivityMaster::joinedMarkerMenuid();

        $services = [
            'IPS', 'IAS', 'IRS(IT)', 'IRMS', 'IRS(CCE)', 'IDES', 'IFS', 'IFS(AIS)',
            'RBFC', 'IIS', 'IAAS', 'RBPS', 'IDAS', 'RBCS', 'ITS', 'IPTAFS', 'ICAS', 'IPOS', 'ICLS',
        ];

        $rawCounts = $joinedCode !== null
            ? $this->svc->getServiceWiseJoinedCounts($joinedCode)
            : [];

        $counts = [];
        foreach ($services as $svc) {
            $counts[$svc] = $rawCounts[$svc] ?? 0;
        }

        return view('admin.fc-activities.reports.service-wise', compact('counts', 'services'));
    }
}
