<?php

namespace App\Http\Controllers;

use App\Models\OtActivity;
use App\Models\OtDetail;
use App\Services\ActivityService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * StatusController
 *
 * Converted from all department showstatus*.php files.
 *
 * Original pattern (repeated 6 times with different activity code):
 *   while($row = fetch all ot_details)
 *     query otactivity_details where username=... AND activity='xxx'
 *     if empty → red cell
 *     else → show 'Done' / 'Joined' / 'Issued'
 *
 * Optimised: single query with LEFT JOIN / eager loading instead of N+1 queries.
 *
 * Routes:
 *   GET /status/admin       → admin()      (showstatusadmin.php  - activity: joined)
 *   GET /status/security    → security()   (showstatussecurity.php - activity: idcard)
 *   GET /status/it          → it()         (showstatusit.php     - activity: biometric)
 *   GET /status/training    → training()   (showstatustrg.php    - activity: trgind)
 *   GET /status/medical     → medical()    (showstatus.php       - activity: height/weight/pulse/bp)
 *   GET /status/shop        → shop()       (showstatusshop.php   - activity: souvenir)
 *   GET /status/all         → all()        (showstatusot.php     - all 6 activities)
 */
class StatusController extends Controller
{
    public function __construct(private ActivityService $svc) {}

    // ── Shared: build OT list with activity status ────────────────────────────

    /**
     * For single-activity status pages (admin, security, it, training, shop).
     * Original N+1 loop → optimised with indexed lookup.
     */
    private function singleActivityStatus(string $activityCode): array
    {
        $ots = OtDetail::active()->orderBy('otcode')->get();

        // Get all done usernames in one query (replaces per-OT subquery)
        $done = OtActivity::where('activity', $activityCode)
            ->pluck('activityval', 'username')
            ->toArray();

        $count = count($done);
        $total = $ots->count();

        $rows = $ots->map(fn($ot) => [
            'otname'  => $ot->otname,
            'otcode'  => $ot->otcode,
            'value'   => $done[$ot->username] ?? null,
            'done'    => isset($done[$ot->username]),
        ]);

        return compact('rows', 'count', 'total');
    }

    // ── Admin (showstatusadmin.php) ───────────────────────────────────────────
    /** Tracks: joined count vs not joined (hardcoded 653 in original) */
    public function admin(): View
    {
        $data = $this->singleActivityStatus('joined');
        return view('status.admin', $data);
    }

    // ── Security (showstatussecurity.php) ─────────────────────────────────────
    /** Tracks: idcard issued */
    public function security(): View
    {
        $data = $this->singleActivityStatus('idcard');
        return view('status.security', $data);
    }

    // ── IT (showstatusit.php) ─────────────────────────────────────────────────
    /** Tracks: biometric done */
    public function it(): View
    {
        $data = $this->singleActivityStatus('biometric');
        return view('status.it', $data);
    }

    // ── Training (showstatustrg.php) ──────────────────────────────────────────
    /** Tracks: trgind (training induction) */
    public function training(): View
    {
        $data = $this->singleActivityStatus('trgind');
        return view('status.training', $data);
    }

    // ── Shop/Souvenir (showstatusshop.php) ────────────────────────────────────
    /** Tracks: souvenir kit issued */
    public function shop(): View
    {
        $data = $this->singleActivityStatus('souvenir');
        return view('status.shop', $data);
    }

    // ── Medical (showstatus.php) ──────────────────────────────────────────────
    /**
     * Original showstatus.php: tracks height, weight, pulse, bp, vialtube, bloodsample
     * Multi-activity per OT — 6 subqueries per row → optimised to single lookup.
     */
    public function medical(): View
    {
        $codes = ['height','weight','pulse','bp','vialtube','bloodsample'];
        $ots   = OtDetail::active()->orderBy('otcode')->get();

        // Single query: get all relevant activities indexed by [username][activity]
        $actMap = OtActivity::whereIn('activity', $codes)
            ->get()
            ->groupBy('username')
            ->map(fn($acts) => $acts->pluck('activityval', 'activity'));

        $rows = $ots->map(function ($ot) use ($actMap, $codes) {
            $vals = $actMap[$ot->username] ?? collect();
            return [
                'otname'      => $ot->otname,
                'otcode'      => $ot->otcode,
                'activities'  => array_combine($codes, array_map(fn($c) => $vals[$c] ?? null, $codes)),
            ];
        });

        return view('status.medical', compact('rows', 'codes'));
    }

    // ── All activities overview (showstatusot.php) ────────────────────────────
    /**
     * Original: per-row queries for joined, idcard, biometric, trgind, souvenir, height
     * Shows all 6 dept activities in one table (master view).
     */
    public function all(): View
    {
        $codes = ['joined','idcard','biometric','trgind','souvenir','height'];
        $ots   = OtDetail::active()->orderBy('otcode')->get();

        $actMap = OtActivity::whereIn('activity', $codes)
            ->get()
            ->groupBy('username')
            ->map(fn($acts) => $acts->pluck('activityval', 'activity'));

        $rows = $ots->map(function ($ot) use ($actMap, $codes) {
            $vals = $actMap[$ot->username] ?? collect();
            return [
                'otname'     => $ot->otname,
                'otcode'     => $ot->otcode,
                'mobileno'   => $ot->mobileno,
                'service'    => $ot->service,
                'activities' => array_combine($codes, array_map(fn($c) => $vals[$c] ?? null, $codes)),
            ];
        });

        return view('status.all', compact('rows', 'codes'));
    }
}
