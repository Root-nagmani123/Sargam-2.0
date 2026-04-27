<?php

namespace App\Http\Controllers;

use App\Models\OtActivity;
use App\Models\OtDetail;
use App\Services\ActivityService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * ReportController
 *
 * Converted from:
 *   showreportall.php   → summary()         — dept count table with links
 *   showotjoined.php    → byDepartment()     — OTs who completed activity for a dept
 *   shownotjoined.php   → notJoined()        — OTs who have NOT joined yet
 *   servicewisest.php   → serviceWise()      — joined counts grouped by service type
 *
 * Routes:
 *   GET /reports/summary            → summary()
 *   GET /reports/department/{dept}  → byDepartment()
 *   GET /reports/not-joined         → notJoined()
 *   GET /reports/service-wise       → serviceWise()
 */
class ReportController extends Controller
{
    public function __construct(private ActivityService $svc) {}

    // ── showreportall.php ─────────────────────────────────────────────────────
    /**
     * Original: 6 separate COUNT queries (one per department activity).
     * Optimised: single grouped query via ActivityService::getDeptCounts()
     */
    public function summary(): View
    {
        $counts = $this->svc->getDeptCounts();
        return view('reports.summary', compact('counts'));
    }

    // ── showotjoined.php ──────────────────────────────────────────────────────
    /**
     * Original: switch on $dep → query otactivity_details where activity='xxx'
     *           → nested loop to fetch ot_details per row (N+1)
     * Optimised: single JOIN query.
     *
     * Dept→activity map is in OtActivity::DEPT_ACTIVITY
     */
    public function byDepartment(string $dept): View
    {
        $deptMap = OtActivity::DEPT_ACTIVITY;

        if (!isset($deptMap[$dept])) {
            abort(404, "Unknown department: $dept");
        }

        $actCode = $deptMap[$dept];

        // Original nested queries replaced with single JOIN
        $ots = OtDetail::join('otactivity_details', function ($join) use ($actCode) {
                $join->on('ot_details.username', '=', 'otactivity_details.username')
                     ->where('otactivity_details.activity', '=', $actCode)
                     ->where('otactivity_details.status', '=', 1);
            })
            ->select('ot_details.otname', 'ot_details.otcode', 'ot_details.mobileno', 'ot_details.service')
            ->orderBy('ot_details.service')
            ->get();

        return view('reports.by-department', compact('ots', 'dept', 'actCode'));
    }

    // ── shownotjoined.php ─────────────────────────────────────────────────────
    /**
     * Original: fetches all OTs, then per-row checks if 'joined' activity exists.
     * Red cell if no joined record. Also shows joined/not-joined count.
     */
    public function notJoined(): View
    {
        $allOts = OtDetail::active()->orderBy('otcode')->get();

        $joinedUsernames = OtActivity::where('activity', 'joined')
            ->pluck('activityval', 'username')
            ->toArray();

        $rows = $allOts->map(fn($ot) => [
            'otname'  => $ot->otname,
            'otcode'  => $ot->otcode,
            'joined'  => isset($joinedUsernames[$ot->username]),
        ]);

        $joinedCount    = count($joinedUsernames);
        $notJoinedCount = $allOts->count() - $joinedCount;

        return view('reports.not-joined', compact('rows', 'joinedCount', 'notJoinedCount'));
    }

    // ── servicewisest.php ─────────────────────────────────────────────────────
    /**
     * Original: nested while loop → O(N²) in PHP.
     * Optimised: single DB GROUP BY query.
     *
     * Original tracked services: IPS,IAS,IRS(IT),IRMS,IRS(CCE),IDES,IFS,IFS(AIS),
     *   RBFC,IIS,IAAS,RBPS,IDAS,RBCS,ITS,IPTAFS,ICAS,IPOS,ICLS
     */
    public function serviceWise(): View
    {
        $services = [
            'IPS','IAS','IRS(IT)','IRMS','IRS(CCE)','IDES','IFS','IFS(AIS)',
            'RBFC','IIS','IAAS','RBPS','IDAS','RBCS','ITS','IPTAFS','ICAS','IPOS','ICLS',
        ];

        // Single optimised query replacing original O(N²) nested loops
        $rawCounts = $this->svc->getServiceWiseCounts();

        $counts = [];
        foreach ($services as $svc) {
            $counts[$svc] = $rawCounts[$svc] ?? 0;
        }

        return view('reports.service-wise', compact('counts', 'services'));
    }
}
